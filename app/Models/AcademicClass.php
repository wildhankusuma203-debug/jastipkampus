<?php
// File: app/Models/AcademicClass.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicClass extends Model
{
    protected $table = 'classes';

    protected $fillable = ['study_program_id', 'name', 'cohort_year', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class, 'study_program_id');
    }
}