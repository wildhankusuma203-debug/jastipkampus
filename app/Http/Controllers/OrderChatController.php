<?php

namespace App\Http\Controllers;

use App\Helpers\FormatHelper;
use App\Models\Order;
use App\Models\OrderChat;
use App\Services\AuditAndNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderChatController extends Controller
{
    public function sendMessage(Request $request, Order $order): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $isParticipant = $order->customer_id === $user->id
            || $order->owner_admin_id === $user->id
            || ($order->owner_admin_id === null && $user->isVerifiedAdmin())
            || $user->hasRole('SYSADMIN');

        if (!$isParticipant) {
            return back()->with('error', 'Akses Chat Ditolak: Anda bukan partisipan pesanan ini.');
        }

        $data = $request->validate([
            'message'     => ['required', 'string', 'min:1', 'max:500'],
            'sender_role' => ['nullable', 'in:CUSTOMER,ADMIN,SYSADMIN'],
        ]);

        $cleanMessage = FormatHelper::sanitizeInput($data['message'], 500);
        $senderRole = $data['sender_role'] ?? ($order->customer_id === $user->id ? 'CUSTOMER' : 'ADMIN');

        OrderChat::create([
            'order_id'    => $order->id,
            'sender_id'   => $user->id,
            'sender_name' => $user->name,
            'sender_role' => $senderRole,
            'message'     => $cleanMessage,
        ]);

        $targetReceiverId = $user->id === $order->customer_id
            ? $order->owner_admin_id
            : $order->customer_id;

        if ($targetReceiverId) {
            AuditAndNotificationService::pushNotification(
                $targetReceiverId,
                "Pesan Baru pada \"{$order->title}\"",
                "{$user->name}: \"{$cleanMessage}\"",
                $order->id
            );
        }

        return back()
            ->with('open_chat_order_id', $order->id)
            ->with('success', 'Pesan terkirim.');
    }
}