<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\Faculty;
use App\Models\Role;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class QaEnvironmentController extends Controller
{
    public function toggleMode(Request $request): RedirectResponse
    {
        $current = session('environment_mode', 'PRODUCTION');
        $next = $current === 'PRODUCTION' ? 'QA_DEV' : 'PRODUCTION';
        session(['environment_mode' => $next]);

        return back()->with('info', "Mode Lingkungan diubah ke: {$next}");
    }

    public function provisionQaAccounts(): RedirectResponse
    {
        if (session('environment_mode', 'PRODUCTION') !== 'QA_DEV') {
            return back()->with('error', 'Aktifkan Mode QA terlebih dahulu.');
        }

        DB::transaction(function () {
            $roleCust = Role::firstOrCreate(['name' => 'CUSTOMER'], ['label' => 'Mahasiswa Customer']);
            $roleAdm = Role::firstOrCreate(['name' => 'ADMIN'], ['label' => 'Admin Penyedia Layanan']);
            $roleSys = Role::firstOrCreate(['name' => 'SYSADMIN'], ['label' => 'Administrator Utama']);

            $facIlkom = Faculty::where('code', 'FTI')->first() ?? Faculty::first();
            $facFeb = Faculty::where('code', 'FEB')->first() ?? $facIlkom;

            $prdIf = StudyProgram::where('name', 'Informatika')->first() ?? StudyProgram::first();
            $prdMnj = StudyProgram::where('name', 'Manajemen')->first() ?? $prdIf;

            $clsIfA = AcademicClass::where('name', 'Informatika A')->first() ?? AcademicClass::first();
            $clsMnjA = AcademicClass::where('name', 'Manajemen A')->first() ?? $clsIfA;

            $budi = User::updateOrCreate(
                ['email' => 'budi.santoso@kampus.ac.id'],
                [
                    'name'                      => 'Budi Santoso',
                    'password'                  => Hash::make('Customer#2026'),
                    'university'                => 'Universitas Nusantara',
                    'faculty_id'                => $facIlkom?->id,
                    'study_program_id'          => $prdIf?->id,
                    'class_id'                  => $clsIfA?->id,
                    'nim'                       => '2410511012',
                    'phone'                     => '081234567890',
                    'wallet_balance'            => 0,
                    'show_class_publicly'       => true,
                    'is_suspended'              => false,
                    'admin_verification_status' => 'NOT_SUBMITTED',
                ]
            );
            $budi->roles()->sync([$roleCust->id]);

            $dimas = User::updateOrCreate(
                ['email' => 'dimas.pratama@kampus.ac.id'],
                [
                    'name'                      => 'Dimas Pratama',
                    'password'                  => Hash::make('AdminDimas#2026'),
                    'university'                => 'Universitas Nusantara',
                    'faculty_id'                => $facIlkom?->id,
                    'study_program_id'          => $prdIf?->id,
                    'class_id'                  => $clsIfA?->id,
                    'nim'                       => '2310511088',
                    'phone'                     => '081399887766',
                    'wallet_balance'            => 45000,
                    'show_class_publicly'       => true,
                    'is_suspended'              => false,
                    'admin_verification_status' => 'VERIFIED',
                ]
            );
            $dimas->roles()->sync([$roleCust->id, $roleAdm->id]);

            $siti = User::updateOrCreate(
                ['email' => 'siti.rahma@kampus.ac.id'],
                [
                    'name'                      => 'Siti Rahmawati',
                    'password'                  => Hash::make('AdminSiti#2026'),
                    'university'                => 'Universitas Nusantara',
                    'faculty_id'                => $facFeb?->id,
                    'study_program_id'          => $prdMnj?->id,
                    'class_id'                  => $clsMnjA?->id,
                    'nim'                       => '2410802019',
                    'phone'                     => '081522334455',
                    'wallet_balance'            => 0,
                    'show_class_publicly'       => true,
                    'is_suspended'              => false,
                    'admin_verification_status' => 'VERIFIED',
                ]
            );
            $siti->roles()->sync([$roleCust->id, $roleAdm->id]);

            $sys = User::updateOrCreate(
                ['email' => 'sysadmin@jastipkampus.ac.id'],
                [
                    'name'                      => 'Dr. Hendra Kusuma',
                    'password'                  => Hash::make('SysAdmin#2026'),
                    'university'                => 'Otoritas Sistem JastipKampus',
                    'faculty_id'                => $facIlkom?->id,
                    'study_program_id'          => $prdIf?->id,
                    'class_id'                  => $clsIfA?->id,
                    'nim'                       => 'NIP-19850412',
                    'phone'                     => '0274889900',
                    'wallet_balance'            => 0,
                    'show_class_publicly'       => false,
                    'is_suspended'              => false,
                    'admin_verification_status' => 'VERIFIED',
                ]
            );
            $sys->roles()->sync([$roleSys->id]);
        });

        return back()->with('info', 'Akun uji QA (Customer Budi, Admin Dimas, Admin Siti, SysAdmin Hendra) berhasil disiapkan di MySQL!');
    }

    public function quickSwitch(Request $request): RedirectResponse
    {
        if (session('environment_mode', 'PRODUCTION') !== 'QA_DEV') {
            return back()->with('error', 'Fitur beralih cepat hanya aktif di Mode QA.');
        }

        $email = $request->input('email');
        $targetWorkspace = $request->input('workspace', 'CUSTOMER');
        $user = User::where('email', $email)->first();

        if (!$user) {
            return back()->with('error', 'Klik "Siapkan Akun Uji QA" terlebih dahulu.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        if ($targetWorkspace === 'SYSADMIN') {
            return redirect()->route('sysadmin.dashboard');
        }
        if ($targetWorkspace === 'ADMIN') {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('customer.dashboard');
    }
}