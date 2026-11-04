<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'order_code',
        'customer_id',
        'owner_admin_id',
        'service_id',
        'customer_faculty_id',
        'customer_program_id',
        'customer_class_id',
        'is_custom_request',
        'title',
        'description',
        'items_detail',
        'pickup_location',
        'delivery_location',
        'deadline',
        'notes',
        'supporting_info',
        'supporting_file_path',
        'customer_budget',
        'offered_price',
        'offer_note',
        'agreed_price',
        'status',
        'escrow_status',
        'escrow_payment_method',
        'escrow_paid_at',
        'receipt_proof_path',
        'delivery_proof_path',
    ];

    protected $casts = [
        'is_custom_request' => 'boolean',
        'deadline' => 'datetime',
        'escrow_paid_at' => 'datetime',
        'customer_budget' => 'integer',
        'offered_price' => 'integer',
        'agreed_price' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function ownerAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_admin_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function customerFaculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'customer_faculty_id');
    }

    public function customerProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class, 'customer_program_id');
    }

    public function customerClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'customer_class_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderHistory::class, 'order_id')->oldest();
    }

    public function chats(): HasMany
    {
        return $this->hasMany(OrderChat::class, 'order_id')->oldest();
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class, 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_id');
    }
}