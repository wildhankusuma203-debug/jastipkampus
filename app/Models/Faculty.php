<?php
// File: app/Models/Faculty.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faculty extends Model
{
    protected $fillable = ['code', 'name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function studyPrograms(): HasMany
    {
        return $this->hasMany(StudyProgram::class, 'faculty_id');
    }
}