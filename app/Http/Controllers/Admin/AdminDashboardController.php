<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FormatHelper;
use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\Category;
use App\Models\Faculty;
use App\Models\NotificationItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Service;
use App\Models\StudyProgram;
use App\Models\SystemConfig;
use App\Services\AuditAndNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(Request $request): View
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user()->load(['roles', 'faculty', 'studyProgram', 'academicClass']);
        $adminMenu = $request->query('menu', 'ADM_HOME');
        $editServiceId = $request->query('edit_service_id');

        $systemConfig = SystemConfig::first() ?? new SystemConfig(['platform_name' => 'JastipKampus UPNYK']);
        $faculties = Faculty::orderBy('name')->get();
        $studyPrograms = StudyProgram::orderBy('name')->get();
        $classes = AcademicClass::orderBy('name')->get();
        $categories = Category::where('is_active', true)->withCount('services')->orderBy('name')->get();

        $myServices = Service::with('category')
            ->where('owner_admin_id', $currentUser->id)
            ->latest()
            ->get();

        $serviceToEdit = $editServiceId
            ? $myServices->firstWhere('id', (int) $editServiceId)
            : null;

        // 1. MENU BURSA PERMINTAAN JASTIP (Permintaan Customer yang BELUM DEAL harganya)
        $marketQuery = Order::with(['customer', 'customerFaculty', 'customerProgram', 'customerClass', 'histories'])
            ->whereIn('status', ['WAITING_OFFER', 'WAITING_CUSTOMER_APPROVAL'])
            ->where(function ($q) use ($currentUser) {
                $q->whereNull('owner_admin_id')
                  ->orWhere('owner_admin_id', $currentUser->id);
            });

        if ($request->filled('mkt_faculty_id') && $request->mkt_faculty_id !== 'ALL') {
            $marketQuery->where('customer_faculty_id', $request->mkt_faculty_id);
        }
        $marketRequests = $marketQuery->latest('updated_at')->get();

        // 2. MENU PESANAN, CHAT & BUKTI FOTO (Hanya pesanan yang SUDAH DEAL harganya & ditangani Admin ini)
        $dealOrdersQuery = Order::with(['customer', 'customerFaculty', 'customerProgram', 'customerClass', 'histories', 'chats'])
            ->where('owner_admin_id', $currentUser->id)
            ->whereNotNull('agreed_price')
            ->whereIn('status', [
                'WAITING_REVIEW',
                'APPROVED_FOR_PROCESS',
                'IN_PROGRESS',
                'WAITING_CUSTOMER_COMPLETION',
                'COMPLETED',
                'CANCELLED',
            ]);

        if ($request->filled('req_status') && $request->req_status !== 'ALL') {
            $dealOrdersQuery->where('status', $request->req_status);
        }
        $dealOrders = $dealOrdersQuery->latest('updated_at')->get();

        $myManagedOrders = Order::where('owner_admin_id', $currentUser->id)->get();

        // Hanya ambil notifikasi yang BELUM DIBACA agar tidak mengganggu notifikasi terbaru
        $notifications = NotificationItem::where('user_id', $currentUser->id)
            ->where('is_read', false)
            ->latest()
            ->get();

        return view('admin.dashboard', compact(
            'currentUser',
            'adminMenu',
            'serviceToEdit',
            'systemConfig',
            'faculties',
            'studyPrograms',
            'classes',
            'categories',
            'myServices',
            'marketRequests',
            'dealOrders',
            'myManagedOrders',
            'notifications'
        ));
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:200'],
        ]);

        $cat = Category::create([
            'name'        => FormatHelper::sanitizeInput($data['name'], 80),
            'description' => FormatHelper::sanitizeInput($data['description'] ?? '', 200),
            'created_by'  => $user->id,
            'is_active'   => true,
        ]);

        AuditAndNotificationService::logAudit('ADMIN_SAVE_CATEGORY', (string) $cat->id, $cat->name);

        return redirect()->route('admin.dashboard', ['menu' => 'ADM_CATEGORIES'])
            ->with('success', "Kategori \"{$cat->name}\" berhasil disimpan ke database!");
    }

    /**
     * Hapus Kategori Dinamis agar tidak menumpuk jika sudah tidak digunakan.
     */
    public function destroyCategory(Category $category): RedirectResponse
    {
        $catName = $category->name;
        $category->delete();

        AuditAndNotificationService::logAudit('ADMIN_DELETE_CATEGORY', (string) $category->id, "Menghapus kategori {$catName}");

        return redirect()->route('admin.dashboard', ['menu' => 'ADM_CATEGORIES'])
            ->with('info', "Kategori \"{$catName}\" berhasil dihapus dari database.");
    }

    public function withdrawBalance(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $balance = (int) $user->wallet_balance;

        if ($balance <= 0) {
            return back()->with('error', 'Saldo dompet Escrow Anda masih Rp 0.');
        }

        $data = $request->validate([
            'bank_name'      => ['required', 'string', 'max:60'],
            'account_number' => ['required', 'string', 'max:40'],
        ]);

        $cleanBank = FormatHelper::sanitizeInput($data['bank_name'], 60);
        $cleanAcc = FormatHelper::sanitizeInput($data['account_number'], 40);

        DB::transaction(function () use ($user, $balance, $cleanBank, $cleanAcc) {
            $user->update(['wallet_balance' => 0]);

            Payment::create([
                'order_id'        => null,
                'user_id'         => $user->id,
                'type'            => 'WALLET_WITHDRAWAL',
                'amount'          => $balance,
                'payment_method'  => 'Pencairan Saldo Dompet Admin',
                'bank_or_ewallet' => $cleanBank,
                'account_number'  => $cleanAcc,
                'status'          => 'SUCCESS',
            ]);
        });

        AuditAndNotificationService::logAudit(
            'ADMIN_WITHDRAW_ESCROW',
            (string) $user->id,
            'Pencairan saldo ' . FormatHelper::formatIDR($balance) . " ke {$cleanBank}"
        );

        return back()->with('success', 'Dana ' . FormatHelper::formatIDR($balance) . " berhasil dicairkan ke {$cleanBank} ({$cleanAcc})!");
    }
}