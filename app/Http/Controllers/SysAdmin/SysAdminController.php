<?php

namespace App\Http\Controllers\SysAdmin;

use App\Helpers\FormatHelper;
use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\AdminApplication;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Faculty;
use App\Models\Order;
use App\Models\Report;
use App\Models\Role;
use App\Models\Service;
use App\Models\StudyProgram;
use App\Models\SystemConfig;
use App\Models\User;
use App\Services\AuditAndNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SysAdminController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $currentUser */
        $currentUser = Auth::user()->load('roles');
        $sysAdminMenu = $request->query('menu', 'SYS_HOME');

        $systemConfig = SystemConfig::first() ?? new SystemConfig(['platform_name' => 'JastipKampus']);
        $faculties = Faculty::orderBy('name')->get();
        $studyPrograms = StudyProgram::with('faculty')->orderBy('name')->get();
        $classes = AcademicClass::with('studyProgram')->orderBy('name')->get();
        $users = User::with(['roles', 'faculty', 'studyProgram', 'academicClass'])->latest()->get();
        $adminApplications = AdminApplication::with(['faculty', 'studyProgram', 'academicClass'])->latest('updated_at')->get();
        $categories = Category::latest()->get();
        $services = Service::with('ownerAdmin')->latest()->get();
        $orders = Order::latest()->get();
        $reports = Report::latest()->get();
        $auditLogs = AuditLog::latest()->limit(150)->get();

        $editFaculty = $request->filled('edit_faculty_id') ? Faculty::find($request->edit_faculty_id) : null;

        return view('sysadmin.dashboard', compact(
            'currentUser',
            'sysAdminMenu',
            'systemConfig',
            'faculties',
            'studyPrograms',
            'classes',
            'users',
            'adminApplications',
            'categories',
            'services',
            'orders',
            'reports',
            'auditLogs',
            'editFaculty'
        ));
    }

    /**
     * Stream / Tampilkan File Fisik KTM Privat (Hanya dapat diakses oleh SYSADMIN).
     */
    public function viewPrivateKtm(AdminApplication $application): StreamedResponse
    {
        if (!Storage::disk('local')->exists($application->ktm_private_path)) {
            abort(404, 'Berkas fisik KTM tidak ditemukan di penyimpanan privat.');
        }

        return Storage::disk('local')->response($application->ktm_private_path);
    }

    /**
     * Setujui atau Tolak Permohonan Verifikasi KTM Calon Admin.
     */
    public function verifyAdminApplication(Request $request, AdminApplication $application): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if ($application->user_id === $currentUser->id) {
            return back()->with('error', 'Keamanan Sistem: Dilarang menyetujui permohonan milik sendiri!');
        }

        $data = $request->validate([
            'decision'         => ['required', 'in:VERIFIED,REJECTED'],
            'rejection_reason' => ['nullable', 'string', 'max:250'],
        ]);

        $decision = $data['decision'];
        $cleanReason = FormatHelper::sanitizeInput($data['rejection_reason'] ?? '', 250);

        if ($decision === 'REJECTED' && empty($cleanReason)) {
            return back()->with('error', 'Wajib mencantumkan alasan penolakan permohonan Admin.');
        }

        DB::transaction(function () use ($application, $currentUser, $decision, $cleanReason) {
            $application->update([
                'status'           => $decision,
                'reviewed_by_id'   => $currentUser->id,
                'rejection_reason' => $decision === 'REJECTED' ? $cleanReason : null,
            ]);

            $targetUser = User::with('roles')->find($application->user_id);
            if ($targetUser) {
                $targetUser->update(['admin_verification_status' => $decision]);

                $custRole = Role::firstOrCreate(['name' => 'CUSTOMER'], ['label' => 'Mahasiswa Customer']);
                $admRole = Role::firstOrCreate(['name' => 'ADMIN'], ['label' => 'Admin Penyedia Layanan']);

                if ($decision === 'VERIFIED') {
                    $targetUser->roles()->syncWithoutDetaching([$custRole->id, $admRole->id]);
                } else {
                    $targetUser->roles()->detach($admRole->id);
                }
            }
        });

        AuditAndNotificationService::pushNotification(
            $application->user_id,
            $decision === 'VERIFIED' ? 'Permohonan Admin Disetujui' : 'Permohonan Admin Ditolak',
            $decision === 'VERIFIED'
                ? 'Verifikasi KTM Anda telah disetujui Administrator Utama. Hak akses Admin kini aktif.'
                : "Permohonan Admin Anda ditolak. Alasan: {$cleanReason}"
        );

        AuditAndNotificationService::logAudit(
            $decision === 'VERIFIED' ? 'SYSADMIN_APPROVE_ADMIN' : 'SYSADMIN_REJECT_ADMIN',
            (string) $application->user_id,
            "Perubahan peran {$application->full_name} -> {$decision}"
        );

        return back()->with(
            $decision === 'VERIFIED' ? 'success' : 'info',
            $decision === 'VERIFIED'
                ? "Permohonan {$application->full_name} disetujui! Hak akses ADMIN diberikan."
                : "Permohonan {$application->full_name} telah ditolak."
        );
    }

    /**
     * Tangguhkan (Suspend) atau Aktifkan Kembali Akun Pengguna.
     */
    public function toggleSuspendUser(User $user): RedirectResponse
    {
        if ($user->hasRole('SYSADMIN')) {
            return back()->with('error', 'Akun Administrator Utama tidak dapat ditangguhkan.');
        }

        $nextSuspended = !$user->is_suspended;
        $user->update(['is_suspended' => $nextSuspended]);

        AuditAndNotificationService::logAudit(
            $nextSuspended ? 'SYSADMIN_SUSPEND_USER' : 'SYSADMIN_REACTIVATE_USER',
            (string) $user->id,
            "Status akun {$user->name}: " . ($nextSuspended ? 'Ditangguhkan' : 'Aktif')
        );

        return back()->with(
            'info',
            $nextSuspended
                ? "Akun {$user->name} berhasil ditangguhkan."
                : "Akun {$user->name} diaktifkan kembali."
        );
    }

    /**
     * Simpan atau Toggle Fakultas.
     */
    public function saveFaculty(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'faculty_id' => ['nullable', 'integer'],
            'name'       => ['required', 'string', 'max:100'],
            'code'       => ['required', 'string', 'max:20'],
        ]);

        $cleanName = FormatHelper::sanitizeInput($data['name'], 100);
        $cleanCode = strtoupper(FormatHelper::sanitizeInput($data['code'], 20));

        if (!empty($data['faculty_id'])) {
            $fac = Faculty::findOrFail($data['faculty_id']);
            $fac->update(['name' => $cleanName, 'code' => $cleanCode]);
        } else {
            $fac = Faculty::create(['name' => $cleanName, 'code' => $cleanCode, 'is_active' => true]);
        }

        AuditAndNotificationService::logAudit('SYSADMIN_SAVE_FACULTY', (string) $fac->id, $fac->name);

        return redirect()->route('sysadmin.dashboard', ['menu' => 'SYS_FACULTIES'])
            ->with('success', "Data Fakultas \"{$fac->name}\" berhasil disimpan!");
    }

    public function toggleFaculty(Faculty $faculty): RedirectResponse
    {
        $faculty->update(['is_active' => !$faculty->is_active]);
        return back()->with('info', "Status Fakultas \"{$faculty->name}\" diperbarui.");
    }

    /**
     * Simpan atau Toggle Program Studi.
     */
    public function saveProgram(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'faculty_id' => ['required', 'integer', 'exists:faculties,id'],
            'name'       => ['required', 'string', 'max:100'],
            'degree'     => ['nullable', 'string', 'max:15'],
        ]);

        $prd = StudyProgram::create([
            'faculty_id' => $data['faculty_id'],
            'name'       => FormatHelper::sanitizeInput($data['name'], 100),
            'degree'     => FormatHelper::sanitizeInput($data['degree'] ?? 'S1', 15),
            'is_active'  => true,
        ]);

        AuditAndNotificationService::logAudit('SYSADMIN_SAVE_PROGRAM', (string) $prd->id, $prd->name);

        return back()->with('success', "Program Studi \"{$prd->name}\" berhasil disimpan!");
    }

    public function toggleProgram(StudyProgram $program): RedirectResponse
    {
        $program->update(['is_active' => !$program->is_active]);
        return back()->with('info', "Status Program Studi \"{$program->name}\" diperbarui.");
    }

    /**
     * Simpan atau Toggle Kelas.
     */
    public function saveClass(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'study_program_id' => ['required', 'integer', 'exists:study_programs,id'],
            'name'             => ['required', 'string', 'max:80'],
            'cohort_year'      => ['required', 'string', 'max:10'],
        ]);

        $cls = AcademicClass::create([
            'study_program_id' => $data['study_program_id'],
            'name'             => FormatHelper::sanitizeInput($data['name'], 80),
            'cohort_year'      => FormatHelper::sanitizeInput($data['cohort_year'], 10),
            'is_active'        => true,
        ]);

        AuditAndNotificationService::logAudit('SYSADMIN_SAVE_CLASS', (string) $cls->id, $cls->name);

        return back()->with('success', "Kelas \"{$cls->name}\" berhasil disimpan!");
    }

    public function toggleClass(AcademicClass $academicClass): RedirectResponse
    {
        $academicClass->update(['is_active' => !$academicClass->is_active]);
        return back()->with('info', "Status Kelas \"{$academicClass->name}\" diperbarui.");
    }

    /**
     * Moderasi (Take Down / Aktifkan Kembali) Layanan oleh Administrator Utama.
     */
    public function moderateService(Service $service): RedirectResponse
    {
        $nextState = !$service->is_active;
        $service->update([
            'is_active'    => $nextState,
            'is_published' => $nextState,
        ]);

        AuditAndNotificationService::logAudit(
            'SYSADMIN_MODERATE_SERVICE',
            (string) $service->id,
            "{$service->name} -> Aktif: " . ($nextState ? 'Ya' : 'Tidak')
        );

        return back()->with(
            'info',
            $nextState
                ? "Layanan \"{$service->name}\" diaktifkan kembali."
                : "Layanan \"{$service->name}\" dinonaktifkan oleh Administrator Utama."
        );
    }

    /**
     * Selesaikan Laporan Masalah.
     */
    public function resolveReport(Request $request, Report $report): RedirectResponse
    {
        $report->update([
            'status'          => 'RESOLVED',
            'resolution_note' => FormatHelper::sanitizeInput($request->input('resolution_note', 'Telah ditindaklanjuti oleh Administrator Utama'), 200),
            'resolved_by_id'  => Auth::id(),
        ]);

        AuditAndNotificationService::logAudit('SYSADMIN_RESOLVE_REPORT', (string) $report->id, $report->target_title);

        return back()->with('success', 'Laporan berhasil diselesaikan dan dicatat di Audit Log.');
    }
}