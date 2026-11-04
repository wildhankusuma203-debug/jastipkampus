<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FormatHelper;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Service;
use App\Services\AuditAndNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminServiceController extends Controller
{
    public function save(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $data = $request->validate([
            'service_id'       => ['nullable', 'integer'],
            'name'             => ['required', 'string', 'max:120'],
            'category_id'      => ['required', 'integer', 'exists:categories,id'],
            'description'      => ['required', 'string', 'max:600'],
            'price'            => ['required', 'numeric', 'min:1'],
            'requirements'     => ['nullable', 'string', 'max:300'],
            'requirement_file' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'is_active'        => ['nullable', 'boolean'],
        ], [
            'requirement_file.mimes' => 'File syarat / contoh dokumen wajib berformat PDF.',
            'requirement_file.max'   => 'Ukuran file PDF maksimal 5 MB.',
        ]);

        $isActive = $request->boolean('is_active');
        $numericPrice = (int) round((float) $data['price']);
        $category = Category::findOrFail($data['category_id']);

        $uploadedReqPath = null;
        if ($request->hasFile('requirement_file')) {
            $uploadedReqPath = $request->file('requirement_file')->store('service_requirements', 'public');
        }

        if (!empty($data['service_id'])) {
            $existing = Service::findOrFail($data['service_id']);
            if ($existing->owner_admin_id !== $user->id) {
                AuditAndNotificationService::logAudit(
                    'RBAC_BLOCK_FOREIGN_SERVICE_EDIT',
                    (string) $existing->id,
                    'Percobaan mengedit layanan milik Admin lain ditolak.'
                );
                return back()->with('error', 'Otorisasi Ditolak: Anda dilarang mengedit atau mengubah layanan milik Admin lain!');
            }

            $existing->update([
                'name'                  => FormatHelper::sanitizeInput($data['name'], 120),
                'category_id'           => $category->id,
                'description'           => FormatHelper::sanitizeInput($data['description'], 600),
                'price'                 => $numericPrice,
                'requirements'          => FormatHelper::sanitizeInput($data['requirements'] ?? '', 300),
                'requirement_file_path' => $uploadedReqPath ?? $existing->requirement_file_path,
                'is_active'             => $isActive,
                'is_published'          => $isActive,
                'faculty_id'            => $user->faculty_id,
                'study_program_id'      => $user->study_program_id,
                'class_id'              => $user->class_id,
                'show_class_publicly'   => (bool) ($user->show_class_publicly ?? true),
            ]);

            $service = $existing;
        } else {
            $service = Service::create([
                'owner_admin_id'        => $user->id,
                'category_id'           => $category->id,
                'name'                  => FormatHelper::sanitizeInput($data['name'], 120),
                'description'           => FormatHelper::sanitizeInput($data['description'], 600),
                'price'                 => $numericPrice,
                'requirements'          => FormatHelper::sanitizeInput($data['requirements'] ?? '', 300),
                'requirement_file_path' => $uploadedReqPath,
                'is_active'             => $isActive,
                'is_published'          => $isActive,
                'faculty_id'            => $user->faculty_id,
                'study_program_id'      => $user->study_program_id,
                'class_id'              => $user->class_id,
                'show_class_publicly'   => (bool) ($user->show_class_publicly ?? true),
            ]);
        }

        AuditAndNotificationService::logAudit(
            'ADMIN_SAVE_SERVICE',
            (string) $service->id,
            "{$service->name} (" . FormatHelper::formatIDR($numericPrice) . ") - Aktif: " . ($isActive ? 'Ya' : 'Tidak')
        );

        return redirect()->route('admin.dashboard', ['menu' => 'ADM_SERVICES'])
            ->with(
                'success',
                $isActive
                    ? "Layanan \"{$service->name}\" berhasil dipublikasikan!"
                    : "Layanan \"{$service->name}\" telah disimpan sebagai nonaktif."
            );
    }

    public function toggleStatus(Service $service): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($service->owner_admin_id !== $user->id) {
            return back()->with('error', 'Otorisasi Ditolak: Anda dilarang mengubah layanan milik Admin lain!');
        }

        $nextState = !$service->is_active;
        $service->update([
            'is_active'    => $nextState,
            'is_published' => $nextState,
        ]);

        AuditAndNotificationService::logAudit(
            'ADMIN_TOGGLE_SERVICE',
            (string) $service->id,
            "{$service->name} -> Aktif: " . ($nextState ? 'Ya' : 'Tidak')
        );

        return back()->with(
            'success',
            $nextState
                ? "Layanan \"{$service->name}\" berhasil dipublikasikan!"
                : "Layanan \"{$service->name}\" telah dinonaktifkan."
        );
    }
}