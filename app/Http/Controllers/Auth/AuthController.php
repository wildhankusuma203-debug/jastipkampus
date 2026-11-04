<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\FormatHelper;
use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\Faculty;
use App\Models\Role;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\AuditAndNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Proses Login Terverifikasi.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $cleanEmail = strtolower(trim($credentials['email']));
        $user = User::with('roles')->whereRaw('LOWER(email) = ?', [$cleanEmail])->first();

        if (!$user) {
            AuditAndNotificationService::logAudit('AUTH_LOGIN_FAILED', $cleanEmail, 'Email tidak ditemukan');
            return back()->with('error', 'Kredensial tidak valid (Email atau kata sandi salah).')->withInput();
        }

        if ($user->is_suspended) {
            AuditAndNotificationService::logAudit('AUTH_LOGIN_SUSPENDED', (string) $user->id, 'Percobaan login akun ditangguhkan', $user);
            return back()->with('error', 'Akun Anda sedang ditangguhkan oleh Administrator Utama.');
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            AuditAndNotificationService::logAudit('AUTH_LOGIN_WRONG_PASSWORD', (string) $user->id, 'Kata sandi salah ditolak', $user);
            return back()->with('error', 'Kredensial tidak valid (Email atau kata sandi salah).')->withInput();
        }

        Auth::login($user);
        $request->session()->regenerate();

        AuditAndNotificationService::logAudit('AUTH_LOGIN_SUCCESS', (string) $user->id, 'Login terverifikasi berhasil', $user);

        if ($user->hasRole('SYSADMIN')) {
            return redirect()->route('sysadmin.dashboard')
                ->with('success', 'Selamat datang kembali, ' . $user->name . '!');
        }

        return redirect()->route('customer.dashboard')
            ->with('success', 'Selamat datang kembali, ' . $user->name . '!');
    }

    /**
     * Registrasi Akun Mahasiswa Baru (Otomatis hanya mendapat role CUSTOMER).
     */
    public function registerCustomer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'min:3', 'max:100'],
            'email'            => ['required', 'email', 'max:150', 'unique:users,email'],
            'password'         => ['required', 'string', 'min:8'],
            'university'       => ['nullable', 'string', 'max:120'],
            'faculty_id'       => ['required', 'integer', 'exists:faculties,id'],
            'study_program_id' => ['required', 'integer', 'exists:study_programs,id'],
            'class_id'         => ['required', 'integer', 'exists:classes,id'],
            'nim'              => ['nullable', 'string', 'max:30'],
            'phone'            => ['nullable', 'string', 'max:25'],
        ]);

        // Validasi relasi berjenjang Fakultas -> Program Studi -> Kelas
        $validProgram = StudyProgram::where('id', $data['study_program_id'])
            ->where('faculty_id', $data['faculty_id'])
            ->where('is_active', true)
            ->first();

        $validClass = AcademicClass::where('id', $data['class_id'])
            ->where('study_program_id', $data['study_program_id'])
            ->where('is_active', true)
            ->first();

        if (!$validProgram || !$validClass) {
            return back()
                ->with('error', 'Pilihan Fakultas, Program Studi, dan Kelas tidak saling berhubungan!')
                ->withInput();
        }

        $user = DB::transaction(function () use ($data) {
            $newCustomer = User::create([
                'name'                      => FormatHelper::sanitizeInput($data['name'], 100),
                'email'                     => strtolower(trim($data['email'])),
                'password'                  => Hash::make($data['password']),
                'university'                => FormatHelper::sanitizeInput($data['university'] ?? 'Universitas Nusantara', 120),
                'faculty_id'                => $data['faculty_id'],
                'study_program_id'          => $data['study_program_id'],
                'class_id'                  => $data['class_id'],
                'nim'                       => FormatHelper::sanitizeInput($data['nim'] ?? '', 30),
                'phone'                     => FormatHelper::sanitizeInput($data['phone'] ?? '', 25),
                'wallet_balance'            => 0,
                'show_class_publicly'       => true,
                'is_suspended'              => false,
                'admin_verification_status' => 'NOT_SUBMITTED',
            ]);

            $customerRole = Role::firstOrCreate(['name' => 'CUSTOMER'], ['label' => 'Mahasiswa Customer']);
            $newCustomer->roles()->sync([$customerRole->id]);

            return $newCustomer;
        });

        Auth::login($user);
        $request->session()->regenerate();

        AuditAndNotificationService::logAudit(
            'REGISTER_CUSTOMER',
            (string) $user->id,
            'Registrasi akun CUSTOMER baru: ' . $user->name,
            $user
        );

        return redirect()->route('customer.dashboard')
            ->with('success', 'Registrasi berhasil! Akun Anda aktif dengan peran CUSTOMER.');
    }

    /**
     * Inisialisasi Satu Kali Administrator Utama Pertama (Hanya jika belum ada SYSADMIN).
     */
    public function initFirstSysAdmin(Request $request): RedirectResponse
    {
        $sysAdminExists = User::whereHas('roles', fn($q) => $q->where('name', 'SYSADMIN'))->exists();
        if ($sysAdminExists) {
            return back()->with('error', 'Administrator Utama sudah diinisialisasi. Tidak dapat membuat ulang dari publik.');
        }

        $data = $request->validate([
            'name'     => ['required', 'string', 'min:3', 'max:100'],
            'email'    => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:10'],
        ]);

        $sysUser = DB::transaction(function () use ($data) {
            $firstFac = Faculty::first();
            $firstPrd = StudyProgram::first();
            $firstCls = AcademicClass::first();

            $u = User::create([
                'name'                      => FormatHelper::sanitizeInput($data['name'], 100),
                'email'                     => strtolower(trim($data['email'])),
                'password'                  => Hash::make($data['password']),
                'university'                => 'Otoritas Sistem JastipKampus',
                'faculty_id'                => $firstFac?->id,
                'study_program_id'          => $firstPrd?->id,
                'class_id'                  => $firstCls?->id,
                'nim'                       => 'SYS-AUTHORITY',
                'phone'                     => '-',
                'wallet_balance'            => 0,
                'show_class_publicly'       => false,
                'is_suspended'              => false,
                'admin_verification_status' => 'VERIFIED',
            ]);

            $sysRole = Role::firstOrCreate(['name' => 'SYSADMIN'], ['label' => 'Administrator Utama Sistem']);
            $u->roles()->sync([$sysRole->id]);

            return $u;
        });

        Auth::login($sysUser);
        $request->session()->regenerate();

        AuditAndNotificationService::logAudit(
            'SYSADMIN_INITIALIZED',
            (string) $sysUser->id,
            'Inisialisasi pertama Administrator Utama selesai',
            $sysUser
        );

        return redirect()->route('sysadmin.dashboard')
            ->with('success', 'Administrator Utama berhasil diinisialisasi secara aman!');
    }

    /**
     * Mengakhiri sesi autentikasi pengguna.
     */
    public function logout(Request $request): RedirectResponse
    {
        /** @var User|null $actor */
        $actor = Auth::user();
        if ($actor) {
            AuditAndNotificationService::logAudit('USER_LOGOUT', (string) $actor->id, 'Pengguna mengakhiri sesi autentikasi', $actor);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('public.landing')
            ->with('info', 'Sesi autentikasi Anda telah diakhiri dengan aman.');
    }
}