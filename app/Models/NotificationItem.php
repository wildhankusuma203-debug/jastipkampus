<?php
// File: app/Models/NotificationItem.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationItem extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'related_order_id',
        'title',
        'message',
        'is_read',
    ];

    protected $casts = ['is_read' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}