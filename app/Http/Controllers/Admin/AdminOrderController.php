<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FormatHelper;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\Payment;
use App\Services\AuditAndNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminOrderController extends Controller
{
    public function acceptCustomerBudget(Order $order): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($order->owner_admin_id && $order->owner_admin_id !== $user->id) {
            return back()->with('error', 'Pesanan ini sudah diambil oleh Admin lain.');
        }
        if ($order->status !== 'WAITING_OFFER') {
            return back()->with('error', 'Pesanan ini tidak berada dalam bursa penawaran.');
        }

        $budgetInt = (int) $order->customer_budget;
        if ($budgetInt <= 0) {
            return back()->with('error', 'Patokan harga Customer tidak valid.');
        }

        DB::transaction(function () use ($order, $user, $budgetInt) {
            $order->update([
                'owner_admin_id' => $user->id,
                'offered_price'  => $budgetInt,
                'agreed_price'   => $budgetInt,
                'offer_note'     => 'Admin menyetujui harga Customer',
                'status'         => 'APPROVED_FOR_PROCESS',
            ]);

            OrderHistory::create([
                'order_id'   => $order->id,
                'actor_id'   => $user->id,
                'actor_name' => $user->name,
                'status'     => 'APPROVED_FOR_PROCESS',
                'note'       => "Admin {$user->name} menyetujui harga Customer sebesar " . FormatHelper::formatIDR($budgetInt) . '. Menunggu pembayaran Escrow Customer.',
            ]);
        });

        AuditAndNotificationService::pushNotification(
            $order->customer_id,
            'Admin Menyetujui Harga Anda! Segera Bayar Escrow',
            "Admin {$user->name} bersedia mengerjakan \"{$order->title}\" sesuai harga " . FormatHelper::formatIDR($budgetInt) . ". Silakan bayar ke Escrow!",
            $order->id
        );

        AuditAndNotificationService::logAudit('ADMIN_ACCEPT_CUSTOMER_BUDGET', $order->order_code, FormatHelper::formatIDR($budgetInt));

        return redirect()->route('admin.dashboard', ['menu' => 'ADM_INCOMING_REQUESTS'])
            ->with('success', 'Deal harga ' . FormatHelper::formatIDR($budgetInt) . '! Pesanan kini dipindahkan ke menu Pesanan, Chat & Bukti Foto.');
    }

    public function sendOffer(Request $request, Order $order): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($order->owner_admin_id && $order->owner_admin_id !== $user->id) {
            return back()->with('error', 'Pesanan ini sudah ditangani oleh Admin lain.');
        }
        if ($order->agreed_price !== null) {
            return back()->with('error', 'Pembatasan Keamanan: Harga yang sudah disepakati tidak dapat diubah secara sepihak!');
        }
        if (!FormatHelper::canTransitionOrder($order->status, 'WAITING_CUSTOMER_APPROVAL')) {
            return back()->with('error', 'Pesanan ini tidak berada dalam status menunggu penawaran harga.');
        }

        $data = $request->validate([
            'offered_price' => ['required', 'numeric', 'min:1'],
            'offer_note'    => ['nullable', 'string', 'max:250'],
        ]);

        $priceInt = (int) round((float) $data['offered_price']);
        $cleanNote = FormatHelper::sanitizeInput($data['offer_note'] ?? 'Nego penyesuaian biaya operasional', 250);

        DB::transaction(function () use ($order, $user, $priceInt, $cleanNote) {
            $order->update([
                'owner_admin_id' => $user->id,
                'offered_price'  => $priceInt,
                'offer_note'     => $cleanNote,
                'status'         => 'WAITING_CUSTOMER_APPROVAL',
            ]);

            OrderHistory::create([
                'order_id'   => $order->id,
                'actor_id'   => $user->id,
                'actor_name' => $user->name,
                'status'     => 'WAITING_CUSTOMER_APPROVAL',
                'note'       => "Admin {$user->name} mengajukan nego harga " . FormatHelper::formatIDR($priceInt) . " ({$cleanNote})",
            ]);
        });

        AuditAndNotificationService::pushNotification(
            $order->customer_id,
            'Nego Penawaran Harga Jastip Masuk',
            "Admin {$user->name} menawarkan harga " . FormatHelper::formatIDR($priceInt) . " untuk \"{$order->title}\". Anda bisa Setujui, Nego Lagi, atau Tolak.",
            $order->id
        );

        AuditAndNotificationService::logAudit('ADMIN_SEND_OFFER', $order->order_code, 'Nego ' . FormatHelper::formatIDR($priceInt));

        return back()->with('success', 'Nego penawaran harga berhasil dikirim kepada Customer!');
    }

    public function decline(Request $request, Order $order): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($order->owner_admin_id && $order->owner_admin_id !== $user->id) {
            return back()->with('error', 'Anda tidak berwenang menolak pesanan milik Admin lain.');
        }

        $data = $request->validate([
            'decline_reason' => ['required', 'string', 'min:3', 'max:200'],
        ]);

        $cleanReason = FormatHelper::sanitizeInput($data['decline_reason'], 200);

        if ($order->is_custom_request && $order->escrow_status !== 'HELD_IN_ESCROW') {
            DB::transaction(function () use ($order, $user, $cleanReason) {
                $order->update([
                    'owner_admin_id' => null,
                    'offered_price'  => null,
                    'agreed_price'   => null,
                    'offer_note'     => null,
                    'status'         => 'WAITING_OFFER',
                ]);

                OrderHistory::create([
                    'order_id'   => $order->id,
                    'actor_id'   => $user->id,
                    'actor_name' => $user->name,
                    'status'     => 'WAITING_OFFER',
                    'note'       => "Admin {$user->name} melepas permintaan ({$cleanReason}). Pesanan tetap terbuka di bursa untuk Admin lain.",
                ]);
            });

            return back()->with('info', 'Permintaan dilepas kembali ke bursa agar dapat diambil Admin lain.');
        }

        $wasEscrowHeld = $order->escrow_status === 'HELD_IN_ESCROW';

        DB::transaction(function () use ($order, $user, $cleanReason, $wasEscrowHeld) {
            $order->update([
                'owner_admin_id' => $user->id,
                'status'         => 'CANCELLED',
                'escrow_status'  => $wasEscrowHeld ? 'REFUNDED' : $order->escrow_status,
            ]);

            if ($wasEscrowHeld) {
                Payment::create([
                    'order_id'       => $order->id,
                    'user_id'        => $order->customer_id,
                    'type'           => 'ESCROW_REFUND',
                    'amount'         => (int) $order->agreed_price,
                    'payment_method' => $order->escrow_payment_method,
                    'status'         => 'SUCCESS',
                ]);
            }

            OrderHistory::create([
                'order_id'   => $order->id,
                'actor_id'   => $user->id,
                'actor_name' => $user->name,
                'status'     => 'CANCELLED',
                'note'       => "Permintaan ditolak oleh Admin. Alasan: {$cleanReason}" . ($wasEscrowHeld ? ' (Dana Escrow dikembalikan ke Customer)' : ''),
            ]);
        });

        AuditAndNotificationService::pushNotification(
            $order->customer_id,
            'Permintaan Jastip Ditolak Admin',
            "Permintaan \"{$order->title}\" tidak dapat diproses. Alasan: {$cleanReason}",
            $order->id
        );

        AuditAndNotificationService::logAudit('ADMIN_DECLINE_ORDER', $order->order_code, $cleanReason);

        return back()->with('info', 'Permintaan telah ditolak dengan alasan yang tercatat.');
    }

    /**
     * Upload Foto Bukti Berurutan:
     * 1. Bukti 1 (RECEIPT): Bukti pengambilan barang / KTM sudah diambil / hasil print sudah jadi -> langsung dikirim ke Customer.
     * 2. Bukti 2 (DELIVERY): Wajib sudah mengunggah Bukti 1 terlebih dahulu sebelum bisa mengunggah Bukti Penyerahan!
     */
    public function uploadProof(Request $request, Order $order): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($order->owner_admin_id && $order->owner_admin_id !== $user->id) {
            return back()->with('error', 'Otorisasi Ditolak: Anda bukan Admin penanggung jawab pesanan ini.');
        }

        if ($order->escrow_status !== 'HELD_IN_ESCROW') {
            return back()->with('error', 'Customer belum membayar ke Escrow. Tunggu pembayaran sebelum memproses & mengunggah bukti.');
        }

        $data = $request->validate([
            'proof_type' => ['required', 'in:RECEIPT,DELIVERY'],
            'proof_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        $proofType = $data['proof_type'];

        // SYARAT WAJIB BERURUTAN: Bukti 2 (DELIVERY) ditolak jika Bukti 1 (RECEIPT) belum diunggah!
        if ($proofType === 'DELIVERY' && empty($order->receipt_proof_path)) {
            return back()->with('error', 'Wajib Berurutan: Anda harus melampirkan & mengirim Bukti 1 (Foto Pengambilan Barang / Hasil Print Jadi) terlebih dahulu kepada Customer sebelum mengunggah Bukti 2 (Penyerahan)!');
        }

        $file = $request->file('proof_file');
        $storedPath = $file->store("order_proofs/{$order->id}", 'public');

        DB::transaction(function () use ($order, $user, $proofType, $storedPath) {
            if ($proofType === 'RECEIPT') {
                $order->update(['receipt_proof_path' => $storedPath]);
            } else {
                $order->update(['delivery_proof_path' => $storedPath]);
            }

            OrderHistory::create([
                'order_id'   => $order->id,
                'actor_id'   => $user->id,
                'actor_name' => $user->name,
                'status'     => $order->status,
                'note'       => $proofType === 'RECEIPT'
                    ? 'Admin melampirkan & mengirim Bukti 1 (Foto Bukti Pengambilan / Barang Sudah Jadi) kepada Customer'
                    : 'Admin melampirkan & mengirim Bukti 2 (Foto Bukti Penyerahan Barang/Dokumen) kepada Customer',
            ]);
        });

        AuditAndNotificationService::pushNotification(
            $order->customer_id,
            $proofType === 'RECEIPT'
                ? 'Bukti 1: Barang Diambil / Selesai Dicetak!'
                : 'Bukti 2: Bukti Penyerahan Barang Diunggah!',
            $proofType === 'RECEIPT'
                ? "Admin {$user->name} telah mengirimkan foto bukti pengambilan / barang sudah jadi untuk pesanan \"{$order->title}\". Silakan cek di menu Pesanan Saya!"
                : "Admin {$user->name} telah mengunggah foto bukti penyerahan untuk pesanan \"{$order->title}\".",
            $order->id
        );

        return back()->with(
            'success',
            $proofType === 'RECEIPT'
                ? 'Bukti 1 (Pengambilan/Barang Jadi) berhasil dikirim ke Customer! Sekarang Anda dapat melanjutkan pengantaran dan mengunggah Bukti 2 (Penyerahan).'
                : 'Bukti 2 (Penyerahan) berhasil dikirim ke Customer! Silakan klik tombol "Konfirmasi Barang Diserahkan".'
        );
    }

    /**
     * Perbarui Status Pesanan oleh Admin.
     * Untuk masuk ke WAITING_CUSTOMER_COMPLETION, Admin WAJIB sudah mengunggah Bukti 1 (receipt_proof_path) DAN Bukti 2 (delivery_proof_path).
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($order->owner_admin_id && $order->owner_admin_id !== $user->id) {
            AuditAndNotificationService::logAudit(
                'RBAC_BLOCK_FOREIGN_ORDER_UPDATE',
                $order->order_code,
                'Percobaan mengubah pesanan milik Admin lain ditolak.'
            );
            return back()->with('error', 'Otorisasi Ditolak: Anda tidak boleh mengubah pesanan milik Admin lain!');
        }

        if ($order->escrow_status !== 'HELD_IN_ESCROW') {
            return back()->with('error', 'Keamanan Escrow: Pesanan tidak boleh diproses sebelum Customer menyetorkan dana ke Rekening Bersama (Escrow)!');
        }

        $nextStatus = $request->input('next_status');
        if ($nextStatus === 'COMPLETED') {
            return back()->with('error', 'Keamanan Escrow: Hanya Customer yang dapat menekan tombol Selesai untuk mencairkan dana!');
        }

        if ($nextStatus === 'WAITING_CUSTOMER_COMPLETION') {
            if (!$order->receipt_proof_path) {
                return back()->with('error', 'Syarat Belum Lengkap: Anda wajib mengunggah Bukti 1 (Foto Pengambilan / Barang Sudah Jadi) terlebih dahulu!');
            }
            if (!$order->delivery_proof_path) {
                return back()->with('error', 'Syarat Belum Lengkap: Anda wajib mengunggah Bukti 2 (Foto Penyerahan Barang) terlebih dahulu!');
            }
        }

        if (!FormatHelper::canTransitionOrder($order->status, (string) $nextStatus)) {
            return back()->with('error', 'Transisi status pesanan tidak sah.');
        }

        $statusMap = FormatHelper::orderStatusMap();
        $label = $statusMap[$nextStatus]['label'] ?? $nextStatus;
        $note = FormatHelper::sanitizeInput(
            $request->input('note', "Status diperbarui menjadi {$label}"),
            240
        );

        DB::transaction(function () use ($order, $user, $nextStatus, $note) {
            $order->update([
                'owner_admin_id' => $user->id,
                'status'         => $nextStatus,
            ]);

            OrderHistory::create([
                'order_id'   => $order->id,
                'actor_id'   => $user->id,
                'actor_name' => $user->name,
                'status'     => $nextStatus,
                'note'       => $note,
            ]);
        });

        AuditAndNotificationService::pushNotification(
            $order->customer_id,
            $nextStatus === 'WAITING_CUSTOMER_COMPLETION'
                ? 'Barang Telah Diserahkan! Mohon Konfirmasi Selesai'
                : "Pesanan Sedang Diproses Admin",
            $nextStatus === 'WAITING_CUSTOMER_COMPLETION'
                ? "Admin {$user->name} telah menyelesaikan pengantaran \"{$order->title}\" lengkap dengan Bukti 1 & Bukti 2. Silakan klik tombol \"Pesanan Selesai\" untuk mencairkan dana Escrow."
                : "Admin {$user->name} mulai memproses pesanan \"{$order->title}\". Tunggu kiriman foto bukti pengambilan/barang jadi dari Admin.",
            $order->id
        );

        AuditAndNotificationService::logAudit('ADMIN_UPDATE_ORDER_STATUS', $order->order_code, "{$order->status} -> {$nextStatus}");

        return back()->with(
            'success',
            $nextStatus === 'WAITING_CUSTOMER_COMPLETION'
                ? 'Konfirmasi penyerahan terkirim! Dana Escrow akan cair ke dompet Anda setelah Customer menekan "Pesanan Selesai".'
                : 'Pesanan kini berstatus Sedang Diproses! Silakan unggah Bukti 1 (Foto Pengambilan/Barang Jadi) untuk dikirim ke Customer.'
        );
    }
}