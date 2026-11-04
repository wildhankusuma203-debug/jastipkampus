<?php
// File: app/Models/OrderChat.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderChat extends Model
{
    protected $fillable = [
        'order_id',
        'sender_id',
        'sender_name',
        'sender_role',
        'message',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}