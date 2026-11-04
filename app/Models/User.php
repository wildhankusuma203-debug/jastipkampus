<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use Notifiable;

    protected $attributes = [
        'university'                => 'Universitas Nusantara',
        'wallet_balance'            => 0,
        'show_class_publicly'       => true,
        'is_suspended'              => false,
        'admin_verification_status' => 'NOT_SUBMITTED',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'university',
        'faculty_id',
        'study_program_id',
        'class_id',
        'nim',
        'phone',
        'wallet_balance',
        'show_class_publicly',
        'is_suspended',
        'admin_verification_status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'nim',
        'phone',
    ];

    protected function casts(): array
    {
        return [
            'password'            => 'hashed',
            'wallet_balance'      => 'integer',
            'show_class_publicly' => 'boolean',
            'is_suspended'        => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    /**
     * Cek apakah user memiliki role tertentu dan tidak sedang ditangguhkan.
     */
    public function hasRole(string $roleName): bool
    {
        if ($this->is_suspended) {
            return false;
        }
        return $this->roles->contains('name', strtoupper($roleName));
    }

    public function isVerifiedAdmin(): bool
    {
        return $this->hasRole('ADMIN') && $this->admin_verification_status === 'VERIFIED';
    }

    public function roleNamesString(): string
    {
        return $this->roles->pluck('name')->implode(', ');
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'faculty_id');
    }

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class, 'study_program_id');
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'owner_admin_id');
    }

    public function customerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function managedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'owner_admin_id');
    }

    public function adminApplications(): HasMany
    {
        return $this->hasMany(AdminApplication::class, 'user_id');
    }

    public function notificationsList(): HasMany
    {
        return $this->hasMany(NotificationItem::class, 'user_id')->latest();
    }
}