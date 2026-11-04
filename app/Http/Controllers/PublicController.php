<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\Faculty;
use App\Models\Service;
use App\Models\StudyProgram;
use App\Models\SystemConfig;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PublicController extends Controller
{
    /**
     * Tampilkan halaman gerbang publik atau arahkan ke dashboard jika sudah login.
     */
    public function index(): View|RedirectResponse
    {
        if (auth()->check()) {
            /** @var User $user */
            $user = auth()->user();
            if ($user->hasRole('SYSADMIN')) {
                return redirect()->route('sysadmin.dashboard');
            }
            return redirect()->route('customer.dashboard');
        }

        $systemConfig = SystemConfig::first() ?? new SystemConfig(['platform_name' => 'JastipKampus']);

        // Hanya layanan yang aktif DAN dipublikasikan yang boleh tampil di halaman publik
        $services = Service::with(['ownerAdmin', 'category', 'faculty', 'studyProgram', 'academicClass'])
            ->where('is_active', true)
            ->where('is_published', true)
            ->latest()
            ->limit(20)
            ->get();

        $faculties = Faculty::where('is_active', true)->orderBy('name')->get();
        $studyPrograms = StudyProgram::where('is_active', true)->orderBy('name')->get();
        $classes = AcademicClass::where('is_active', true)->orderBy('name')->get();

        $hasSysAdmin = User::whereHas('roles', fn($q) => $q->where('name', 'SYSADMIN'))->exists();

        return view('public.landing', compact(
            'systemConfig',
            'services',
            'faculties',
            'studyPrograms',
            'classes',
            'hasSysAdmin'
        ));
    }
}