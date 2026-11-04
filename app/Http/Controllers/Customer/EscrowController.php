<?php

namespace App\Http\Controllers\Customer;

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

class EscrowController extends Controller
{
    /**
     * Setor Dana ke Rekening Bersama (Simulasi Escrow Internal).
     */
    public function pay(Request $request, Order $order): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($order->customer_id !== $user->id) {
            return back()->with('error', 'Otorisasi Ditolak: Anda bukan pemilik pesanan ini.');
        }
        if (!$order->agreed_price || $order->agreed_price <= 0) {
            return back()->with('error', 'Harga belum disepakati. Tunggu dan setujui penawaran harga terlebih dahulu.');
        }
        if (in_array($order->escrow_status, ['HELD_IN_ESCROW', 'RELEASED_TO_ADMIN'], true)) {
            return back()->with('info', 'Pesanan ini sudah dibayar ke Rekening Bersama (Escrow).');
        }

        $data = $request->validate([
            'payment_method' => ['required', 'string', 'max:80'],
        ]);

        $cleanMethod = FormatHelper::sanitizeInput($data['payment_method'], 80);

        DB::transaction(function () use ($order, $user, $cleanMethod) {
            $order->update([
                'escrow_status'         => 'HELD_IN_ESCROW',
                'escrow_payment_method' => $cleanMethod,
                'escrow_paid_at'        => now(),
            ]);

            Payment::create([
                'order_id'       => $order->id,
                'user_id'        => $user->id,
                'type'           => 'ESCROW_DEPOSIT',
                'amount'         => (int) $order->agreed_price,
                'payment_method' => $cleanMethod,
                'status'         => 'SUCCESS',
            ]);

            OrderHistory::create([
                'order_id'   => $order->id,
                'actor_id'   => $user->id,
                'actor_name' => $user->name,
                'status'     => $order->status,
                'note'       => 'Customer menyetorkan ' . FormatHelper::formatIDR($order->agreed_price) . " ke Rekening Bersama (Escrow) via {$cleanMethod}.",
            ]);
        });

        if ($order->owner_admin_id) {
            AuditAndNotificationService::pushNotification(
                $order->owner_admin_id,
                'Dana Escrow Telah Masuk!',
                "Customer {$user->name} telah menyetorkan " . FormatHelper::formatIDR($order->agreed_price) . " ke Rekening Bersama untuk \"{$order->title}\". Silakan mulai proses pesanan.",
                $order->id
            );
        }

        AuditAndNotificationService::logAudit(
            'ESCROW_PAYMENT_HELD',
            $order->order_code,
            'Setoran Escrow ' . FormatHelper::formatIDR($order->agreed_price) . " via {$cleanMethod}"
        );

        return back()->with('success', 'Pembayaran Escrow ' . FormatHelper::formatIDR($order->agreed_price) . ' berhasil diamankan di Rekening Bersama!');
    }
}