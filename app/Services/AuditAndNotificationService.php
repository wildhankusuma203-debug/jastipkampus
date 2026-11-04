<?php

namespace App\Services;

use App\Helpers\FormatHelper;
use App\Models\AuditLog;
use App\Models\NotificationItem;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuditAndNotificationService
{
    /**
     * Catat tindakan penting ke tabel audit_logs (Tanpa menyimpan NIM, No HP, atau Password).
     */
    public static function logAudit(string $action, ?string $targetId, string $sanitizedDetail, ?User $actorOverride = null): void
    {
        /** @var User|null $actor */
        $actor = $actorOverride ?? Auth::user();

        AuditLog::create([
            'actor_id'   => $actor ? $actor->id : null,
            'actor_name' => $actor ? $actor->name : 'Pengunjung',
            'actor_role' => $actor ? ($actor->roleNamesString() ?: 'NONE') : 'NONE',
            'action'     => FormatHelper::sanitizeInput($action, 80),
            'target_id'  => $targetId ? FormatHelper::sanitizeInput((string) $targetId, 80) : null,
            'detail'     => FormatHelper::sanitizeInput($sanitizedDetail, 240),
        ]);
    }

    /**
     * Kirim notifikasi internal ke pengguna tertentu.
     */
    public static function pushNotification(int $targetUserId, string $title, string $message, ?int $relatedOrderId = null): void
    {
        if ($targetUserId <= 0) {
            return;
        }

        NotificationItem::create([
            'user_id'          => $targetUserId,
            'related_order_id' => $relatedOrderId,
            'title'            => FormatHelper::sanitizeInput($title, 140),
            'message'          => FormatHelper::sanitizeInput($message, 290),
            'is_read'          => false,
        ]);
    }
}