<?php

namespace App\Http\Middleware;

use App\Services\AuditAndNotificationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $requiredRole): Response
    {
        if (!Auth::check()) {
            return redirect()->route('public.landing')
                ->with('error', 'Akses Ditolak: Anda wajib login terlebih dahulu.');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user()->fresh(['roles']);

        if ($user->is_suspended) {
            AuditAndNotificationService::logAudit(
                'AUTH_BLOCKED_SUSPENDED',
                (string) $user->id,
                'Akses ditolak karena akun sedang ditangguhkan',
                $user
            );
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('public.landing')
                ->with('error', 'Akun Anda sedang ditangguhkan oleh Administrator Utama.');
        }

        $roleUpper = strtoupper($requiredRole);

        if ($roleUpper === 'ADMIN') {
            if (!$user->isVerifiedAdmin()) {
                AuditAndNotificationService::logAudit(
                    'RBAC_BLOCK_ADMIN_DASHBOARD',
                    (string) $user->id,
                    'Akses Admin Dashboard ditolak (Status verifikasi: ' . $user->admin_verification_status . ')'
                );
                return redirect()->route('customer.dashboard', ['menu' => 'CUST_REGISTER_ADMIN'])
                    ->with('error', 'Otorisasi Ditolak: Anda belum menjadi Admin terverifikasi. Ajukan permohonan & unggah KTM terlebih dahulu.');
            }
            return $next($request);
        }

        if ($roleUpper === 'SYSADMIN') {
            if (!$user->hasRole('SYSADMIN')) {
                AuditAndNotificationService::logAudit(
                    'RBAC_BLOCK_SYSADMIN_DASHBOARD',
                    (string) $user->id,
                    'Percobaan akses tidak sah ke Administrator Utama Dashboard ditolak.'
                );
                return redirect()->route('customer.dashboard')
                    ->with('error', 'Otorisasi Ditolak: Hanya Administrator Utama yang memiliki izin mengakses halaman ini.');
            }
            return $next($request);
        }

        if ($roleUpper === 'CUSTOMER') {
            if (!$user->hasRole('CUSTOMER') && !$user->hasRole('ADMIN')) {
                return redirect()->route('sysadmin.dashboard')
                    ->with('error', 'Otorisasi Ditolak: Akun Administrator Utama dikhususkan untuk pengelolaan sistem.');
            }
            return $next($request);
        }

        return $next($request);
    }
}