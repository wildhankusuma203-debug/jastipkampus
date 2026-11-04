<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\FormatHelper;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Services\AuditAndNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerOrderController extends Controller
{
    /**
     * Buat Pesanan Layanan Resmi atau Permintaan Jastip Bebas (Tanpa minimal karakter judul & deskripsi).
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $data = $request->validate([
            'service_id'        => ['required', 'string'],
            'title'             => ['required', 'string', 'max:150'],
            'description'       => ['required', 'string', 'max:1000'],
            'items_detail'      => ['required', 'string', 'max:300'],
            'pickup_location'   => ['required', 'string', 'max:200'],
            'delivery_location' => ['required', 'string', 'max:200'],
            'deadline'          => ['required', 'date'],
            'customer_budget'   => ['nullable', 'numeric', 'min:0'],
            'notes'             => ['nullable', 'string', 'max:300'],
            'supporting_info'   => ['nullable', 'string', 'max:300'],
            'supporting_file'   => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ], [
            'supporting_file.mimes' => 'File lampiran syarat/dokumen cetak wajib berformat PDF.',
            'supporting_file.max'   => 'Ukuran file PDF maksimal 5 MB.',
        ]);

        $chosenService = null;
        if ($data['service_id'] !== 'CUSTOM') {
            $chosenService = Service::with('ownerAdmin')
                ->where('id', $data['service_id'])
                ->where('is_active', true)
                ->where('is_published', true)
                ->first();

            if (!$chosenService) {
                return back()->with('error', 'Layanan yang dipilih sudah tidak aktif dan tidak dapat dipesan.')->withInput();
            }
        }

        $isCustom = $chosenService === null;
        $proposedBudget = $isCustom
            ? (int) round((float) ($data['customer_budget'] ?? 0))
            : (int) $chosenService->price;

        if ($isCustom && $proposedBudget <= 0) {
            return back()->with('error', 'Untuk permintaan jastip bebas, Anda wajib memasukkan Patokan Harga Awal (lebih dari Rp 0).')->withInput();
        }

        $supportingFilePath = null;
        if ($request->hasFile('supporting_file')) {
            $supportingFilePath = $request->file('supporting_file')->store('customer_files', 'public');
        }

        $initStatus = $isCustom ? 'WAITING_OFFER' : 'WAITING_REVIEW';

        $order = DB::transaction(function () use ($user, $data, $chosenService, $isCustom, $proposedBudget, $initStatus, $supportingFilePath) {
            $newOrder = Order::create([
                'order_code'           => 'ORD-' . strtoupper(bin2hex(random_bytes(4))),
                'customer_id'          => $user->id,
                'owner_admin_id'       => $chosenService ? $chosenService->owner_admin_id : null,
                'service_id'           => $chosenService ? $chosenService->id : null,
                'customer_faculty_id'  => $user->faculty_id,
                'customer_program_id'  => $user->study_program_id,
                'customer_class_id'    => $user->class_id,
                'is_custom_request'    => $isCustom,
                'title'                => FormatHelper::sanitizeInput($data['title'], 150),
                'description'          => FormatHelper::sanitizeInput($data['description'], 1000),
                'items_detail'         => FormatHelper::sanitizeInput($data['items_detail'], 300),
                'pickup_location'      => FormatHelper::sanitizeInput($data['pickup_location'], 200),
                'delivery_location'    => FormatHelper::sanitizeInput($data['delivery_location'], 200),
                'deadline'             => $data['deadline'],
                'notes'                => FormatHelper::sanitizeInput($data['notes'] ?? '', 300),
                'supporting_info'      => FormatHelper::sanitizeInput($data['supporting_info'] ?? '', 300),
                'supporting_file_path' => $supportingFilePath,
                'customer_budget'      => $proposedBudget,
                'offered_price'        => $chosenService ? $chosenService->price : null,
                'offer_note'           => $chosenService ? 'Harga layanan resmi yang dipublikasikan Admin' : null,
                'agreed_price'         => $chosenService ? $chosenService->price : null,
                'status'               => $initStatus,
                'escrow_status'        => 'UNPAID',
            ]);

            OrderHistory::create([
                'order_id'   => $newOrder->id,
                'actor_id'   => $user->id,
                'actor_name' => $user->name,
                'status'     => $initStatus,
                'note'       => ($isCustom
                    ? 'Customer membuat permintaan jastip bebas dengan patokan harga awal ' . FormatHelper::formatIDR($proposedBudget)
                    : 'Customer memesan layanan "' . $chosenService->name . '" dengan harga ' . FormatHelper::formatIDR($chosenService->price))
                    . ($supportingFilePath ? ' (Melampirkan berkas PDF)' : ''),
            ]);

            return $newOrder;
        });

        if ($chosenService && $chosenService->owner_admin_id) {
            AuditAndNotificationService::pushNotification(
                $chosenService->owner_admin_id,
                'Pesanan Layanan Baru Masuk',
                "Customer {$user->name} memesan layanan \"{$chosenService->name}\".",
                $order->id
            );
        }

        AuditAndNotificationService::logAudit('CUSTOMER_CREATE_ORDER', $order->order_code, $order->title);

        if (!$isCustom) {
            return redirect()->route('customer.dashboard', ['menu' => 'CUST_MY_ORDERS'])
                ->with('payment_warning_order_id', $order->id)
                ->with('success', 'Pesanan layanan berhasil dibuat! PERINGATAN: Silakan lakukan pembayaran Escrow terlebih dahulu agar pesanan Anda dapat diproses oleh Admin.');
        }

        return redirect()->route('customer.dashboard', ['menu' => 'CUST_MY_ORDERS'])
            ->with('success', 'Permintaan jastip berhasil dipublikasikan ke Bursa Admin!');
    }

    /**
     * Respon Customer terhadap Nego Harga dari Admin:
     * 1. APPROVE (Setujui Harga) -> Masuk ke APPROVED_FOR_PROCESS (Wajib bayar Escrow)
     * 2. COUNTER (Nego Lagi / Ajukan Harga Balik) -> Perbarui customer_budget & kembali ke WAITING_OFFER agar Admin bisa menyetujui atau menawar lagi
     * 3. REJECT (Tolak Nego) -> Lepas owner_admin_id & kembali ke WAITING_OFFER di bursa umum
     */
    public function respondOffer(Request $request, Order $order): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($order->customer_id !== $user->id) {
            return back()->with('error', 'Otorisasi Ditolak: Anda bukan pemilik pesanan ini.');
        }
        if ($order->status !== 'WAITING_CUSTOMER_APPROVAL') {
            return back()->with('error', 'Status pesanan tidak dalam tahap menunggu persetujuan harga.');
        }

        // Dukungan parameter 'decision' (APPROVE / COUNTER / REJECT) maupun kompatibilitas 'is_approved'
        $decision = $request->input('decision');
        if (!$decision) {
            $decision = $request->boolean('is_approved') ? 'APPROVE' : 'REJECT';
        }

        $previousAdminId = $order->owner_admin_id;
        $previousAdminName = $order->ownerAdmin?->name ?? 'Admin';
        $offeredAmount = (int) $order->offered_price;

        // OPSI 2: CUSTOMER MEMILIH "NEGO LAGI" (COUNTER OFFER)
        if ($decision === 'COUNTER') {
            $data = $request->validate([
                'counter_price' => ['required', 'numeric', 'min:1000'],
                'counter_note'  => ['nullable', 'string', 'max:250'],
            ], [
                'counter_price.required' => 'Masukkan nominal harga nego balik Anda.',
                'counter_price.min'      => 'Nominal nego minimal Rp 1.000.',
            ]);

            $counterPrice = (int) round((float) $data['counter_price']);
            $cleanCounterNote = FormatHelper::sanitizeInput($data['counter_note'] ?? 'Customer mengajukan nego harga balik', 250);

            DB::transaction(function () use ($order, $user, $counterPrice, $cleanCounterNote, $offeredAmount, $previousAdminName) {
                $order->update([
                    'customer_budget' => $counterPrice,
                    'offered_price'   => null,
                    'offer_note'      => "Nego balik Customer: {$cleanCounterNote}",
                    'status'          => 'WAITING_OFFER',
                ]);

                OrderHistory::create([
                    'order_id'   => $order->id,
                    'actor_id'   => $user->id,
                    'actor_name' => $user->name,
                    'status'     => 'WAITING_OFFER',
                    'note'       => "Customer belum cocok dengan tawaran " . FormatHelper::formatIDR($offeredAmount) . " dari {$previousAdminName} dan mengajukan NEGO LAGI sebesar " . FormatHelper::formatIDR($counterPrice) . " ({$cleanCounterNote}).",
                ]);
            });

            if ($previousAdminId) {
                AuditAndNotificationService::pushNotification(
                    $previousAdminId,
                    'Customer Mengajukan Nego Lagi!',
                    "Customer {$user->name} mengajukan nego balik sebesar " . FormatHelper::formatIDR($counterPrice) . " untuk \"{$order->title}\". Silakan cek di menu Bursa Permintaan Jastip.",
                    $order->id
                );
            }

            AuditAndNotificationService::logAudit(
                'CUSTOMER_COUNTER_OFFER',
                $order->order_code,
                'Nego balik Customer ' . FormatHelper::formatIDR($counterPrice)
            );

            return redirect()->route('customer.dashboard', ['menu' => 'CUST_MY_ORDERS'])
                ->with('info', 'Nego balik sebesar ' . FormatHelper::formatIDR($counterPrice) . ' berhasil dikirim ke Admin! Menunggu konfirmasi Admin.');
        }

        // OPSI 1 (APPROVE) ATAU OPSI 3 (REJECT)
        $isApproved = ($decision === 'APPROVE');
        $nextStatus = $isApproved ? 'APPROVED_FOR_PROCESS' : 'WAITING_OFFER';

        if (!FormatHelper::canTransitionOrder($order->status, $nextStatus)) {
            return back()->with('error', 'Transisi status tidak diizinkan.');
        }

        DB::transaction(function () use ($order, $user, $isApproved, $nextStatus, $previousAdminName, $offeredAmount) {
            $order->update([
                'owner_admin_id' => $isApproved ? $order->owner_admin_id : null,
                'agreed_price'   => $isApproved ? $offeredAmount : null,
                'offered_price'  => $isApproved ? $offeredAmount : null,
                'offer_note'     => $isApproved ? $order->offer_note : null,
                'status'         => $nextStatus,
            ]);

            OrderHistory::create([
                'order_id'   => $order->id,
                'actor_id'   => $user->id,
                'actor_name' => $user->name,
                'status'     => $nextStatus,
                'note'       => $isApproved
                    ? 'Customer menyetujui nego harga ' . FormatHelper::formatIDR($offeredAmount) . '. Harga dikunci (Wajib Bayar Escrow sebelum diproses).'
                    : 'Customer menolak nego harga ' . FormatHelper::formatIDR($offeredAmount) . ' dari ' . $previousAdminName . '. Pesanan dibuka kembali di bursa untuk Admin lain.',
            ]);
        });

        if ($previousAdminId) {
            AuditAndNotificationService::pushNotification(
                $previousAdminId,
                $isApproved ? 'Deal Harga! Masuk ke Menu Pesanan' : 'Nego Harga Ditolak Customer',
                $isApproved
                    ? "Customer {$user->name} menyetujui harga " . FormatHelper::formatIDR($offeredAmount) . " untuk \"{$order->title}\". Pesanan kini berpindah ke menu Pesanan, Chat & Bukti Foto."
                    : "Customer {$user->name} menolak nego harga Anda untuk \"{$order->title}\". Pesanan dikembalikan ke bursa publik.",
                $order->id
            );
        }

        AuditAndNotificationService::logAudit(
            $isApproved ? 'CUSTOMER_APPROVE_OFFER' : 'CUSTOMER_REJECT_OFFER_REOPEN',
            $order->order_code,
            $isApproved ? 'Setuju harga ' . FormatHelper::formatIDR($offeredAmount) : 'Tolak nego, kembali ke bursa'
        );

        return redirect()->route('customer.dashboard', ['menu' => 'CUST_MY_ORDERS'])
            ->with('payment_warning_order_id', $isApproved ? $order->id : null)
            ->with(
                $isApproved ? 'success' : 'info',
                $isApproved
                    ? 'Harga ' . FormatHelper::formatIDR($offeredAmount) . ' disepakati! PERINGATAN: Segera bayar ke Rekening Bersama (Escrow) agar pesanan dapat diproses oleh Admin.'
                    : 'Penawaran harga ditolak. Pesanan Anda tetap aktif di bursa agar dapat diambil oleh Admin lainnya!'
            );
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($order->customer_id !== $user->id) {
            return back()->with('error', 'Otorisasi Ditolak.');
        }

        if ($order->escrow_status !== 'UNPAID') {
            return back()->with('error', 'Penolakan Sistem: Pesanan yang sudah dibayar ke Rekening Bersama (Escrow) TIDAK DAPAT dibatalkan!');
        }

        if (in_array($order->status, ['IN_PROGRESS', 'WAITING_CUSTOMER_COMPLETION', 'COMPLETED', 'CANCELLED'], true)) {
            return back()->with('error', 'Pesanan yang sedang diproses atau sudah selesai tidak dapat dibatalkan.');
        }

        $reason = FormatHelper::sanitizeInput($request->input('cancel_reason', 'Kebutuhan berubah'), 160);

        DB::transaction(function () use ($order, $user, $reason) {
            $order->update([
                'status' => 'CANCELLED',
            ]);

            OrderHistory::create([
                'order_id'   => $order->id,
                'actor_id'   => $user->id,
                'actor_name' => $user->name,
                'status'     => 'CANCELLED',
                'note'       => "Dibatalkan oleh Customer (sebelum pembayaran): {$reason}",
            ]);
        });

        if ($order->owner_admin_id) {
            AuditAndNotificationService::pushNotification(
                $order->owner_admin_id,
                'Pesanan Dibatalkan Customer',
                "Pesanan \"{$order->title}\" telah dibatalkan oleh Customer sebelum pembayaran.",
                $order->id
            );
        }

        AuditAndNotificationService::logAudit('CUSTOMER_CANCEL_ORDER', $order->order_code, $order->title);

        return back()->with('info', 'Pesanan telah dibatalkan.');
    }

    public function confirmCompletion(Order $order): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($order->customer_id !== $user->id) {
            return back()->with('error', 'Otorisasi Ditolak: Hanya Customer pemilik pesanan yang dapat mengonfirmasi selesai.');
        }
        if ($order->status !== 'WAITING_CUSTOMER_COMPLETION') {
            return back()->with('error', 'Admin belum mengonfirmasi penyerahan barang/dokumen.');
        }

        DB::transaction(function () use ($order, $user) {
            $order->update([
                'status'        => 'COMPLETED',
                'escrow_status' => 'RELEASED_TO_ADMIN',
            ]);

            if ($order->owner_admin_id && $order->agreed_price > 0) {
                $adminUser = User::lockForUpdate()->find($order->owner_admin_id);
                if ($adminUser) {
                    $adminUser->increment('wallet_balance', (int) $order->agreed_price);

                    Payment::create([
                        'order_id'       => $order->id,
                        'user_id'        => $adminUser->id,
                        'type'           => 'ESCROW_RELEASE',
                        'amount'         => (int) $order->agreed_price,
                        'payment_method' => 'Rekening Bersama JastipKampus',
                        'status'         => 'SUCCESS',
                    ]);
                }
            }

            OrderHistory::create([
                'order_id'   => $order->id,
                'actor_id'   => $user->id,
                'actor_name' => $user->name,
                'status'     => 'COMPLETED',
                'note'       => 'Customer mengonfirmasi barang/dokumen diterima dengan baik. Dana Escrow ' . FormatHelper::formatIDR($order->agreed_price) . ' dicairkan ke Saldo Dompet Admin.',
            ]);
        });

        if ($order->owner_admin_id) {
            AuditAndNotificationService::pushNotification(
                $order->owner_admin_id,
                'Pesanan Selesai & Dana Escrow Cair!',
                "Customer {$user->name} telah menekan Selesai pada \"{$order->title}\". Dana " . FormatHelper::formatIDR($order->agreed_price) . " telah masuk ke Saldo Dompet Anda!",
                $order->id
            );
        }

        AuditAndNotificationService::logAudit(
            'CUSTOMER_CONFIRM_COMPLETED_ESCROW_RELEASE',
            $order->order_code,
            'Cair ' . FormatHelper::formatIDR($order->agreed_price)
        );

        return back()->with('success', 'Pesanan Selesai! Dana Escrow ' . FormatHelper::formatIDR($order->agreed_price) . ' telah diteruskan ke Saldo Admin. Silakan berikan ulasan.');
    }
}