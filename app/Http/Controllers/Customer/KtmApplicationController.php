<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\FormatHelper;
use App\Http\Controllers\Controller;
use App\Models\AdminApplication;
use App\Models\SystemConfig;
use App\Services\AuditAndNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KtmApplicationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $config = SystemConfig::first();
        $maxKilobytes = ($config ? $config->max_ktm_size_mb : 2) * 1024;

        $data = $request->validate([
            'full_name' => ['required', 'string', 'min:3', 'max:100'],
            'nim'       => ['required', 'string', 'min:5', 'max:30'],
            'ktm_file'  => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', "max:{$maxKilobytes}"],
        ], [
            'ktm_file.required' => 'Penolakan Keamanan: File fisik KTM wajib diunggah!',
            'ktm_file.mimes'    => 'Format file KTM tidak valid. Gunakan JPG, PNG, atau PDF.',
            'ktm_file.max'      => 'Ukuran file KTM melebihi batas maksimal sistem.',
        ]);

        $file = $request->file('ktm_file');
        $checksum = substr(hash_file('sha256', $file->getRealPath()), 0, 24);
        $safeOriginalName = FormatHelper::sanitizeInput($file->getClientOriginalName(), 120);
        $mimeType = $file->getMimeType() ?? 'application/octet-stream';
        $fileSize = $file->getSize() ?: 0;

        // Simpan ke folder privat (tidak bisa diakses via URL publik tanpa otorisasi)
        $storedPath = $file->storeAs(
            "private/ktm_vault/{$user->id}",
            time() . '_' . $checksum . '.' . $file->getClientOriginalExtension()
        );

        $app = DB::transaction(function () use ($user, $data, $safeOriginalName, $mimeType, $fileSize, $checksum, $storedPath) {
            $newApp = AdminApplication::create([
                'user_id'          => $user->id,
                'full_name'        => FormatHelper::sanitizeInput($data['full_name'], 100),
                'email'            => $user->email,
                'university'       => $user->university,
                'faculty_id'       => $user->faculty_id,
                'study_program_id' => $user->study_program_id,
                'class_id'         => $user->class_id,
                'nim'              => FormatHelper::sanitizeInput($data['nim'], 30),
                'ktm_file_name'    => $safeOriginalName,
                'ktm_file_type'    => $mimeType,
                'ktm_file_size'    => $fileSize,
                'ktm_checksum'     => $checksum,
                'ktm_private_path' => $storedPath,
                'status'           => 'PENDING',
            ]);

            $user->update([
                'nim'                       => FormatHelper::sanitizeInput($data['nim'], 30),
                'admin_verification_status' => 'PENDING',
            ]);

            return $newApp;
        });

        // Jangan simpan NIM di Audit Log demi privasi mahasiswa
        AuditAndNotificationService::logAudit(
            'SUBMIT_ADMIN_APPLICATION',
            (string) $app->id,
            'Permohonan verifikasi Admin dikirim (Checksum KTM: ' . substr($checksum, 0, 10) . ')'
        );

        return redirect()->route('customer.dashboard', ['menu' => 'CUST_REGISTER_ADMIN'])
            ->with('success', 'Permohonan pendaftaran Admin & berkas KTM berhasil disimpan! Status: Menunggu Verifikasi.');
    }
}