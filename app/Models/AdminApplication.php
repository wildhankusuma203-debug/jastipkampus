<?php
// File: app/Models/AdminApplication.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminApplication extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'email',
        'university',
        'faculty_id',
        'study_program_id',
        'class_id',
        'nim',
        'ktm_file_name',
        'ktm_file_type',
        'ktm_file_size',
        'ktm_checksum',
        'ktm_private_path',
        'status',
        'reviewed_by_id',
        'rejection_reason',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }
}