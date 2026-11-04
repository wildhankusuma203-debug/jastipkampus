<?php
// File: app/Models/SystemConfig.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    protected $fillable = [
        'platform_name',
        'allow_cross_faculty_orders',
        'require_ktm_for_admin',
        'max_ktm_size_mb',
    ];

    protected $casts = [
        'allow_cross_faculty_orders' => 'boolean',
        'require_ktm_for_admin' => 'boolean',
        'max_ktm_size_mb' => 'integer',
    ];
}