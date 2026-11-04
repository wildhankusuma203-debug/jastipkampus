<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'owner_admin_id',
        'category_id',
        'name',
        'description',
        'price',
        'requirements',
        'requirement_file_path',
        'is_active',
        'is_published',
        'faculty_id',
        'study_program_id',
        'class_id',
        'show_class_publicly',
    ];

    protected $casts = [
        'price' => 'integer',
        'is_active' => 'boolean',
        'is_published' => 'boolean',
        'show_class_publicly' => 'boolean',
    ];

    public function ownerAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_admin_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
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

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'service_id');
    }
}