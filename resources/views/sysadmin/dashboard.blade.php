@extends('layouts.app')

@section('content')
@php
    $verifStatusMap = \App\Helpers\FormatHelper::verificationStatusMap();
    $pendingAppsCount = $adminApplications->where('status', 'PENDING')->count();
    $openReportsCount = $reports->where('status', 'OPEN')->count();

    $sysNavItems = [
        ['id' => 'SYS_HOME', 'label' => 'Dashboard Utama', 'icon' => 'layout-dashboard', 'badge' => 0],
        ['id' => 'SYS_ADMIN_APPS', 'label' => 'Verifikasi KTM & Admin', 'icon' => 'user-check', 'badge' => $pendingAppsCount],
        ['id' => 'SYS_USERS', 'label' => 'Manajemen Pengguna', 'icon' => 'users', 'badge' => $users->count()],
        ['id' => 'SYS_FACULTIES', 'label' => 'Manajemen Fakultas', 'icon' => 'graduation-cap', 'badge' => 0],
        ['id' => 'SYS_PROGRAMS', 'label' => 'Manajemen Program Studi', 'icon' => 'book-open', 'badge' => 0],
        ['id' => 'SYS_CLASSES', 'label' => 'Manajemen Kelas', 'icon' => 'layers', 'badge' => 0],
        ['id' => 'SYS_SERVICES', 'label' => 'Moderasi Layanan', 'icon' => 'briefcase', 'badge' => 0],
        ['id' => 'SYS_REPORTS', 'label' => 'Laporan Masalah', 'icon' => 'flag', 'badge' => $openReportsCount],
        ['id' => 'SYS_AUDIT', 'label' => 'Audit Log Sistem', 'icon' => 'file-text', 'badge' => $auditLogs->count()],
    ];
@endphp

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- SIDEBAR SYSADMIN -->
    <aside class="lg:col-span-3 space-y-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-4 space-y-3 shadow-sm">
            <div class="p-3 rounded-xl bg-rose-950 text-white space-y-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-rose-300">Administrator Utama</span>
                <p class="font-extrabold text-sm truncate">{{ $currentUser->name }}</p>
                <p class="text-[11px] text-rose-200 truncate">{{ $currentUser->email }}</p>
            </div>

            <nav class="space-y-1">
                @foreach($sysNavItems as $item)
                    @php $isActive = $sysAdminMenu === $item['id']; @endphp
                    <a href="{{ route('sysadmin.dashboard', ['menu' => $item['id']]) }}"
                       class="w-full px-3 py-2 rounded-xl text-xs font-bold flex items-center justify-between transition {{ $isActive ? 'bg-rose-800 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span class="flex items-center gap-2.5">
                            <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 shrink-0"></i>
                            <span class="text-left">{{ $item['label'] }}</span>
                        </span>
                        @if($item['badge'] > 0)
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $isActive ? 'bg-white text-rose-900' : 'bg-rose-100 text-rose-900' }}">
                                {{ $item['badge'] }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>
    </aside>

    <!-- KONTEN SYSADMIN -->
    <section class="lg:col-span-9 space-y-6">

        <!-- 1. DASHBOARD UTAMA -->
        @if($sysAdminMenu === 'SYS_HOME')
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                <h1 class="text-2xl font-extrabold text-slate-900">Dashboard Administrator Utama</h1>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                    <div class="p-3.5 rounded-xl bg-blue-50 border border-blue-200">
                        <span class="text-blue-900 font-semibold">Total Pengguna</span>
                        <p class="text-2xl font-extrabold text-blue-950 mt-1">{{ $users->count() }}</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-teal-50 border border-teal-200">
                        <span class="text-teal-900 font-semibold">Total Admin Terverifikasi</span>
                        <p class="text-2xl font-extrabold text-teal-950 mt-1">{{ $users->where('admin_verification_status', 'VERIFIED')->count() }}</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200">
                        <span class="text-amber-900 font-semibold">Antrean Verifikasi KTM</span>
                        <p class="text-2xl font-extrabold text-amber-950 mt-1">{{ $pendingAppsCount }}</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200">
                        <span class="text-emerald-900 font-semibold">Layanan Aktif</span>
                        <p class="text-2xl font-extrabold text-emerald-950 mt-1">{{ $services->where('is_active', true)->count() }}</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-100 border border-slate-300">
                        <span class="text-slate-800 font-semibold">Total Pesanan</span>
                        <p class="text-2xl font-extrabold text-slate-950 mt-1">{{ $orders->count() }}</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-indigo-50 border border-indigo-200">
                        <span class="text-indigo-900 font-semibold">Dana Aktif di Escrow</span>
                        <p class="text-lg font-extrabold text-indigo-950 mt-1">
                            {{ \App\Helpers\FormatHelper::formatIDR($orders->where('escrow_status', 'HELD_IN_ESCROW')->sum('agreed_price')) }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- 2. VERIFIKASI KTM & PERSETUJUAN ADMIN -->
        @if($sysAdminMenu === 'SYS_ADMIN_APPS')
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 text-xs">
                <h2 class="text-lg font-extrabold text-slate-900">Peninjauan Dokumen Fisik KTM & Persetujuan Admin</h2>
                @forelse($adminApplications as $app)
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-extrabold text-sm text-slate-900">{{ $app->full_name }}</span>
                                <span class="text-slate-500 ml-2">(NIM: {{ $app->nim }} • {{ $app->email }})</span>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full font-bold border {{ $verifStatusMap[$app->status]['badge'] ?? '' }}">
                                {{ $verifStatusMap[$app->status]['label'] ?? $app->status }}
                            </span>
                        </div>
                        <p class="text-slate-700">
                            Afiliasi Akademik: <strong>{{ $app->faculty?->name }}</strong> •
                            <strong>{{ $app->studyProgram?->name }}</strong> •
                            <strong>{{ $app->academicClass?->name }}</strong>
                        </p>
                        <div class="p-3 rounded-lg bg-blue-50 border border-blue-200 text-blue-950 flex flex-wrap items-center justify-between gap-2">
                            <span>
                                File KTM Privat: <strong>{{ $app->ktm_file_name }}</strong> (SHA-256: <code>{{ $app->ktm_checksum }}</code>)
                            </span>
                            <a href="{{ route('sysadmin.ktm.view', $app) }}" target="_blank"
                               class="px-3 py-1 rounded bg-blue-900 text-white font-bold flex items-center gap-1">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                <span>Buka & Periksa Berkas KTM Asli</span>
                            </a>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <form action="{{ route('sysadmin.applications.verify', $app) }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="decision" value="VERIFIED">
                                <button type="submit" class="px-4 py-1.5 rounded-lg bg-emerald-700 text-white font-bold">
                                    Setujui Permohonan Admin
                                </button>
                            </form>
                            <form action="{{ route('sysadmin.applications.verify', $app) }}" method="POST" class="flex-1 flex gap-2">
                                @csrf
                                <input type="hidden" name="decision" value="REJECTED">
                                <input type="text" name="rejection_reason" required placeholder="Alasan penolakan (wajib jika menolak)..." class="flex-1 px-3 py-1.5 rounded-lg border border-slate-300 bg-white">
                                <button type="submit" class="px-4 py-1.5 rounded-lg bg-rose-600 text-white font-bold">Tolak</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-slate-500 italic">Belum ada pengajuan pendaftaran Admin.</p>
                @endforelse
            </div>
        @endif

        <!-- 3. MANAJEMEN PENGGUNA -->
        @if($sysAdminMenu === 'SYS_USERS')
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 text-xs">
                <h2 class="text-lg font-extrabold text-slate-900">Manajemen Akun Pengguna ({{ $users->count() }})</h2>
                @foreach($users as $u)
                    <div class="p-4 rounded-xl border border-slate-200 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-sm text-slate-900">{{ $u->name }}</span>
                            <span class="ml-2 px-2 py-0.5 rounded bg-slate-100 font-bold text-[10px]">{{ $u->roleNamesString() }}</span>
                            <p class="text-slate-500 mt-0.5">{{ $u->email }} • {{ $u->faculty?->name }} • {{ $u->studyProgram?->name }}</p>
                        </div>
                        @if(!$u->hasRole('SYSADMIN'))
                            <form action="{{ route('sysadmin.users.toggle-suspend', $u) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-lg font-bold {{ $u->is_suspended ? 'bg-emerald-700 text-white' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                    {{ $u->is_suspended ? 'Aktifkan Kembali' : 'Tangguhkan (Suspend)' }}
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <!-- 4. MANAJEMEN FAKULTAS -->
        @if($sysAdminMenu === 'SYS_FACULTIES')
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 text-xs">
                <h2 class="text-lg font-extrabold text-slate-900">Manajemen Fakultas (UPN "Veteran" Yogyakarta)</h2>
                <form action="{{ route('sysadmin.faculties.save') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @csrf
                    <input type="hidden" name="faculty_id" value="{{ $editFaculty?->id }}">
                    <input type="text" name="name" required value="{{ $editFaculty?->name }}" placeholder="Nama Fakultas" class="px-3 py-2 rounded-lg border border-slate-300">
                    <input type="text" name="code" required value="{{ $editFaculty?->code }}" placeholder="Kode (mis: FTI / FTME)" class="px-3 py-2 rounded-lg border border-slate-300">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-rose-800 text-white font-bold">
                        {{ $editFaculty ? 'Simpan Perubahan' : '+ Tambah Fakultas' }}
                    </button>
                </form>
                <div class="space-y-2">
                    @foreach($faculties as $f)
                        <div class="p-3 rounded-xl border border-slate-200 flex items-center justify-between">
                            <span class="font-bold">{{ $f->name }} ({{ $f->code }})</span>
                            <div class="flex gap-2">
                                <a href="{{ route('sysadmin.dashboard', ['menu' => 'SYS_FACULTIES', 'edit_faculty_id' => $f->id]) }}" class="px-3 py-1 rounded border border-slate-300 font-bold">Edit</a>
                                <form action="{{ route('sysadmin.faculties.toggle', $f) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 rounded bg-slate-900 text-white font-bold">{{ $f->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- 5. MANAJEMEN PROGRAM STUDI (DIKELOMPOKKAN PER MASING-MASING FAKULTAS) -->
        @if($sysAdminMenu === 'SYS_PROGRAMS')
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-5 text-xs">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900">Manajemen Program Studi (Dikelompokkan per Fakultas)</h2>
                    <p class="text-slate-500">Setiap Program Studi ditampilkan di bawah kelompok Fakultas induknya (contoh: Informatika di Fakultas Teknik Industri, Teknik Pertambangan di Fakultas Teknologi Mineral dan Energi).</p>
                </div>

                <form action="{{ route('sysadmin.programs.save') }}" method="POST" class="p-4 rounded-xl bg-slate-50 border border-slate-200 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @csrf
                    <select name="faculty_id" required class="px-3 py-2 rounded-lg border border-slate-300 bg-white font-semibold">
                        @foreach($faculties as $f)
                            <option value="{{ $f->id }}">{{ $f->name }} ({{ $f->code }})</option>
                        @endforeach
                    </select>
                    <input type="text" name="name" required placeholder="Nama Program Studi Baru" class="px-3 py-2 rounded-lg border border-slate-300 bg-white">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-rose-800 text-white font-bold">+ Tambah Program Studi</button>
                </form>

                <!-- Pengelompokan Program Studi Berdasarkan Fakultas -->
                <div class="space-y-4">
                    @foreach($faculties as $fac)
                        @php
                            $groupedPrograms = $studyPrograms->where('faculty_id', $fac->id);
                        @endphp
                        <div class="rounded-2xl border border-slate-200 overflow-hidden">
                            <div class="bg-slate-900 text-white px-4 py-2.5 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="graduation-cap" class="w-4 h-4 text-rose-300"></i>
                                    <span class="font-extrabold text-sm">{{ $fac->name }} ({{ $fac->code }})</span>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full bg-slate-800 text-teal-300 text-[11px] font-bold">
                                    {{ $groupedPrograms->count() }} Program Studi
                                </span>
                            </div>

                            <div class="p-3 bg-white divide-y divide-slate-100">
                                @forelse($groupedPrograms as $p)
                                    <div class="py-2.5 px-2 flex items-center justify-between hover:bg-slate-50 rounded-lg">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-900 font-bold text-[10px] border border-blue-200">
                                                {{ $p->degree }}
                                            </span>
                                            <span class="font-bold text-slate-900 text-xs">{{ $p->name }}</span>
                                            <span class="text-[10px] px-2 py-0.5 rounded {{ $p->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800' }}">
                                                {{ $p->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </div>
                                        <form action="{{ route('sysadmin.programs.toggle', $p) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 rounded bg-slate-900 hover:bg-slate-700 text-white font-bold text-[11px]">
                                                {{ $p->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="text-slate-400 italic py-2 px-2">Belum ada program studi di fakultas ini.</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- 6. MANAJEMEN KELAS -->
        @if($sysAdminMenu === 'SYS_CLASSES')
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 text-xs">
                <h2 class="text-lg font-extrabold text-slate-900">Manajemen Kelas</h2>
                <form action="{{ route('sysadmin.classes.save') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    @csrf
                    <select name="study_program_id" required class="px-3 py-2 rounded-lg border border-slate-300 bg-white font-semibold">
                        @foreach($studyPrograms as $p)
                            <option value="{{ $p->id }}">{{ $p->faculty?->code }} - {{ $p->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="name" required placeholder="Nama Kelas" class="px-3 py-2 rounded-lg border border-slate-300">
                    <input type="text" name="cohort_year" value="2024" required placeholder="Angkatan" class="px-3 py-2 rounded-lg border border-slate-300">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-rose-800 text-white font-bold">+ Simpan Kelas</button>
                </form>
                <div class="space-y-2">
                    @foreach($classes as $c)
                        <div class="p-3 rounded-xl border border-slate-200 flex items-center justify-between">
                            <span><strong>{{ $c->name }}</strong> ({{ $c->studyProgram?->name }} • Angkatan {{ $c->cohort_year }})</span>
                            <form action="{{ route('sysadmin.classes.toggle', $c) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1 rounded bg-slate-900 text-white font-bold">{{ $c->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- 7. MODERASI LAYANAN -->
        @if($sysAdminMenu === 'SYS_SERVICES')
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-3 text-xs">
                <h2 class="text-lg font-extrabold text-slate-900">Moderasi Layanan Platform (Filter Keamanan Kampus)</h2>
                @forelse($services as $s)
                    <div class="p-3.5 rounded-xl border border-slate-200 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-slate-900">{{ $s->name }}</span>
                            <span class="text-slate-500 ml-2">oleh {{ $s->ownerAdmin?->name }} ({{ \App\Helpers\FormatHelper::formatIDR($s->price) }})</span>
                        </div>
                        <form action="{{ route('sysadmin.services.moderate', $s) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-1 rounded font-bold {{ $s->is_active ? 'bg-rose-600 text-white' : 'bg-emerald-700 text-white' }}">
                                {{ $s->is_active ? 'Nonaktifkan (Take Down)' : 'Aktifkan Kembali' }}
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="text-slate-500 italic">Belum ada layanan yang terdaftar.</p>
                @endforelse
            </div>
        @endif

        <!-- 8. LAPORAN MASALAH -->
        @if($sysAdminMenu === 'SYS_REPORTS')
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-3 text-xs">
                <h2 class="text-lg font-extrabold text-slate-900">Laporan Kendala & Penyalahgunaan ({{ $reports->count() }})</h2>
                @forelse($reports as $r)
                    <div class="p-3.5 rounded-xl border border-slate-200 flex items-center justify-between">
                        <div>
                            <p class="font-bold text-slate-900">{{ $r->target_title }}</p>
                            <p class="text-slate-600">Pelapor: {{ $r->reporter_name }} — "{{ $r->reason }}"</p>
                        </div>
                        @if($r->status === 'OPEN')
                            <form action="{{ route('sysadmin.reports.resolve', $r) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded bg-emerald-700 text-white font-bold">Tandai Selesai</button>
                            </form>
                        @else
                            <span class="text-emerald-700 font-bold">SELESAI</span>
                        @endif
                    </div>
                @empty
                    <p class="text-slate-500 italic">Tidak ada laporan masuk.</p>
                @endforelse
            </div>
        @endif

        <!-- 9. AUDIT LOG SISTEM -->
        @if($sysAdminMenu === 'SYS_AUDIT')
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-3 text-xs">
                <h2 class="text-lg font-extrabold text-slate-900">Audit Log Keamanan Sistem ({{ $auditLogs->count() }})</h2>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @foreach($auditLogs as $log)
                        <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-between">
                            <div>
                                <span class="font-mono font-bold text-rose-800">{{ $log->action }}</span>
                                <span class="text-slate-600 ml-2">oleh <strong>{{ $log->actor_name }}</strong> ({{ $log->actor_role }}): {{ $log->detail }}</span>
                            </div>
                            <span class="text-slate-400">{{ \App\Helpers\FormatHelper::formatWIB($log->created_at) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
</div>
@endsection