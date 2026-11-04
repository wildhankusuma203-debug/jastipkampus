<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\FormatHelper;
use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\Category;
use App\Models\Faculty;
use App\Models\NotificationItem;
use App\Models\Order;
use App\Models\Report;
use App\Models\Review;
use App\Models\Service;
use App\Models\StudyProgram;
use App\Models\SystemConfig;
use App\Services\AuditAndNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerDashboardController extends Controller
{
    public function index(Request $request): View
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user()->load(['roles', 'faculty', 'studyProgram', 'academicClass']);
        $customerMenu = $request->query('menu', 'CUST_HOME');
        $selectedServiceId = $request->query('service_id');

        $systemConfig = SystemConfig::first() ?? new SystemConfig(['platform_name' => 'JastipKampus UPNYK']);
        $faculties = Faculty::where('is_active', true)->orderBy('name')->get();
        $studyPrograms = StudyProgram::where('is_active', true)->orderBy('name')->get();
        $classes = AcademicClass::where('is_active', true)->orderBy('name')->get();
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        $servicesQuery = Service::with(['ownerAdmin', 'category', 'faculty', 'studyProgram', 'academicClass'])
            ->where('is_active', true)
            ->where('is_published', true);

        if ($request->filled('faculty_id') && $request->faculty_id !== 'ALL') {
            $servicesQuery->where('faculty_id', $request->faculty_id);
        }
        if ($request->filled('study_program_id') && $request->study_program_id !== 'ALL') {
            $servicesQuery->where('study_program_id', $request->study_program_id);
        }
        if ($request->filled('class_id') && $request->class_id !== 'ALL') {
            $servicesQuery->where('class_id', $request->class_id);
        }
        if ($request->filled('category_id') && $request->category_id !== 'ALL') {
            $servicesQuery->where('category_id', $request->category_id);
        }
        if ($request->filled('max_price') && is_numeric($request->max_price)) {
            $servicesQuery->where('price', '<=', (int) $request->max_price);
        }
        if ($request->filled('q')) {
            $q = trim($request->q);
            $servicesQuery->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhereHas('ownerAdmin', fn($u) => $u->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('category', fn($c) => $c->where('name', 'like', "%{$q}%"));
            });
        }

        $filteredServices = $servicesQuery->latest()->limit(100)->get();
        $allActiveServices = Service::with(['ownerAdmin', 'category', 'faculty', 'studyProgram'])
            ->where('is_active', true)
            ->where('is_published', true)
            ->latest()
            ->get();

        $selectedServiceObj = $selectedServiceId
            ? $allActiveServices->firstWhere('id', (int) $selectedServiceId)
            : $allActiveServices->first();

        $myOrders = Order::with(['ownerAdmin', 'service', 'histories', 'chats', 'review'])
            ->where('customer_id', $currentUser->id)
            ->latest('updated_at')
            ->get();

        $myActiveOrders = $myOrders->whereNotIn('status', ['COMPLETED', 'CANCELLED']);
        $myCompletedOrders = $myOrders->where('status', 'COMPLETED');
        $myPendingOffers = $myOrders->where('status', 'WAITING_CUSTOMER_APPROVAL');

        $myAdminApp = $currentUser->adminApplications()->latest('updated_at')->first();

        // Hanya ambil notifikasi yang BELUM DIBACA agar notifikasi lama tidak menumpuk
        $notifications = NotificationItem::where('user_id', $currentUser->id)
            ->where('is_read', false)
            ->latest()
            ->get();

        return view('customer.dashboard', compact(
            'currentUser',
            'customerMenu',
            'selectedServiceObj',
            'systemConfig',
            'faculties',
            'studyPrograms',
            'classes',
            'categories',
            'filteredServices',
            'allActiveServices',
            'myOrders',
            'myActiveOrders',
            'myCompletedOrders',
            'myPendingOffers',
            'myAdminApp',
            'notifications'
        ));
    }

    public function markNotificationRead(NotificationItem $notification): RedirectResponse
    {
        if ($notification->user_id === Auth::id()) {
            $notification->update(['is_read' => true]);
        }
        return back()->with('info', 'Notifikasi telah ditandai sudah dibaca.');
    }

    public function markAllNotificationsRead(): RedirectResponse
    {
        NotificationItem::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back()->with('info', 'Seluruh notifikasi telah dibersihkan.');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $data = $request->validate([
            'name'                => ['required', 'string', 'min:3', 'max:100'],
            'university'          => ['required', 'string', 'max:120'],
            'faculty_id'          => ['required', 'integer', 'exists:faculties,id'],
            'study_program_id'    => ['required', 'integer', 'exists:study_programs,id'],
            'class_id'            => ['required', 'integer', 'exists:classes,id'],
            'phone'               => ['nullable', 'string', 'max:25'],
            'show_class_publicly' => ['nullable', 'boolean'],
        ]);

        $validFac = Faculty::where('id', $data['faculty_id'])->where('is_active', true)->exists();
        $validPrd = StudyProgram::where('id', $data['study_program_id'])
            ->where('faculty_id', $data['faculty_id'])
            ->where('is_active', true)
            ->exists();
        $validCls = AcademicClass::where('id', $data['class_id'])
            ->where('study_program_id', $data['study_program_id'])
            ->where('is_active', true)
            ->exists();

        if (!$validFac || !$validPrd || !$validCls) {
            return back()->with('error', 'Validasi Akademik Gagal: Pilihan Fakultas, Program Studi, dan Kelas tidak sesuai!');
        }

        $user->update([
            'name'                => FormatHelper::sanitizeInput($data['name'], 100),
            'university'          => FormatHelper::sanitizeInput($data['university'], 120),
            'faculty_id'          => $data['faculty_id'],
            'study_program_id'    => $data['study_program_id'],
            'class_id'            => $data['class_id'],
            'phone'               => FormatHelper::sanitizeInput($data['phone'] ?? '', 25),
            'show_class_publicly' => $request->boolean('show_class_publicly'),
        ]);

        AuditAndNotificationService::logAudit('UPDATE_STUDENT_PROFILE', (string) $user->id, 'Memperbarui afiliasi akademik mahasiswa');

        return redirect()->route('customer.dashboard', ['menu' => 'CUST_PROFILE'])
            ->with('success', 'Profil akademik mahasiswa berhasil disimpan!');
    }

    public function submitReview(Request $request, Order $order): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($order->customer_id !== $user->id) {
            return back()->with('error', 'Otorisasi Ditolak: Hanya pemilik pesanan yang dapat memberi ulasan.');
        }
        if ($order->status !== 'COMPLETED') {
            return back()->with('error', 'Ulasan hanya dapat diberikan setelah pesanan berstatus Selesai.');
        }
        if (Review::where('order_id', $order->id)->exists()) {
            return back()->with('error', 'Ulasan ganda ditolak: Pesanan ini sudah memiliki ulasan.');
        }

        $data = $request->validate([
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $review = Review::create([
            'order_id'       => $order->id,
            'service_id'     => $order->service_id,
            'customer_id'    => $user->id,
            'owner_admin_id' => $order->owner_admin_id,
            'rating'         => (int) $data['rating'],
            'comment'        => FormatHelper::sanitizeInput($data['comment'], 500),
        ]);

        if ($order->owner_admin_id) {
            AuditAndNotificationService::pushNotification(
                $order->owner_admin_id,
                'Ulasan Baru dari Customer',
                "{$user->name} memberikan rating {$review->rating}/5 pada pesanan \"{$order->title}\".",
                $order->id
            );
        }

        AuditAndNotificationService::logAudit('CUSTOMER_SUBMIT_REVIEW', (string) $review->id, "Order {$order->order_code} ({$review->rating}/5)");

        return back()->with('success', 'Ulasan Anda berhasil disimpan!');
    }

    public function submitReport(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $data = $request->validate([
            'target_title' => ['required', 'string', 'max:120'],
            'reason'       => ['required', 'string', 'min:5', 'max:400'],
        ]);

        $rpt = Report::create([
            'reporter_id'   => $user->id,
            'reporter_name' => $user->name,
            'target_title'  => FormatHelper::sanitizeInput($data['target_title'], 120),
            'reason'        => FormatHelper::sanitizeInput($data['reason'], 400),
            'status'        => 'OPEN',
        ]);

        AuditAndNotificationService::logAudit('CUSTOMER_SUBMIT_REPORT', (string) $rpt->id, $rpt->target_title);

        return back()->with('info', 'Laporan kendala telah dikirim ke Administrator Utama.');
    }
}