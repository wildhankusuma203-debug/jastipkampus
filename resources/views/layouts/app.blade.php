<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $systemConfig->platform_name ?? 'JastipKampus' }} — Platform Jasa Titip Antar Mahasiswa</title>
    <!-- Tailwind CSS & Alpine.js + Lucide Icons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 flex flex-col font-sans antialiased"
      x-data="{
          qaModalOpen: false,
          auditTab: 'AUDIT_SUMMARY',
          notifPerm: ('Notification' in window) ? Notification.permission : 'unsupported',
          requestPushNotif() {
              if (!('Notification' in window)) {
                  alert('Browser ini tidak mendukung Web Push Notification.');
                  return;
              }
              Notification.requestPermission().then(perm => {
                  this.notifPerm = perm;
                  if (perm === 'granted') {
                      new Notification('Notifikasi JastipKampus Aktif!', {
                          body: 'Anda akan menerima peringatan real-time untuk penawaran harga, chat, dan status pesanan.'
                      });
                  }
              });
          }
      }">

    @php
        $envMode = session('environment_mode', 'PRODUCTION');
        $authUser = auth()->user();
        $currentRoute = request()->route() ? request()->route()->getName() : '';
        $activeWorkspace = str_starts_with($currentRoute, 'sysadmin.')
            ? 'SYSADMIN_DASHBOARD'
            : (str_starts_with($currentRoute, 'admin.') ? 'ADMIN_DASHBOARD' : 'CUSTOMER_DASHBOARD');
    @endphp

    <!-- BAR STATUS LINGKUNGAN, DATABASE MYSQL, & PUSH NOTIFICATION -->
    <div class="bg-slate-950 text-slate-200 text-xs py-2 px-4 border-b border-slate-800">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded font-bold bg-emerald-600 text-white">
                    <i data-lucide="database" class="w-3.5 h-3.5"></i>
                    <span>Database MySQL Terhubung (Eloquent ORM)</span>
                </span>

                <span class="px-2 py-0.5 rounded text-[11px] font-bold border {{ $envMode === 'PRODUCTION' ? 'bg-blue-950 text-blue-200 border-blue-700' : 'bg-amber-950 text-amber-200 border-amber-700' }}">
                    Mode: {{ $envMode === 'PRODUCTION' ? 'PRODUKSI (Ketat)' : 'QA / PENGEMBANGAN' }}
                </span>

                <button type="button"
                        @click="requestPushNotif()"
                        class="px-2 py-0.5 rounded text-[11px] font-bold border flex items-center gap-1"
                        :class="notifPerm === 'granted' ? 'bg-emerald-950 text-emerald-200 border-emerald-700' : 'bg-slate-800 hover:bg-slate-700 text-amber-200 border-slate-700'">
                    <i data-lucide="bell" class="w-3 h-3"></i>
                    <span x-text="notifPerm === 'granted' ? 'Push Notif Aktif' : 'Aktifkan Push Notif'"></span>
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if($envMode === 'QA_DEV')
                    <div class="flex flex-wrap items-center gap-1.5 bg-slate-900 px-2 py-1 rounded border border-amber-600/50">
                        <span class="text-amber-300 font-bold text-[11px]">Simulasi QA:</span>
                        <form action="{{ route('qa.provision') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-amber-200 border border-slate-700 font-semibold">
                                Siapkan Akun Uji QA
                            </button>
                        </form>
                        <form action="{{ route('qa.switch') }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="email" value="budi.santoso@kampus.ac.id">
                            <input type="hidden" name="workspace" value="CUSTOMER">
                            <button type="submit" class="px-2 py-0.5 rounded bg-blue-800 text-white font-semibold">Uji Customer</button>
                        </form>
                        <form action="{{ route('qa.switch') }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="email" value="dimas.pratama@kampus.ac.id">
                            <input type="hidden" name="workspace" value="ADMIN">
                            <button type="submit" class="px-2 py-0.5 rounded bg-teal-700 text-white font-semibold">Uji Admin</button>
                        </form>
                        <form action="{{ route('qa.switch') }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="email" value="sysadmin@jastipkampus.ac.id">
                            <input type="hidden" name="workspace" value="SYSADMIN">
                            <button type="submit" class="px-2 py-0.5 rounded bg-rose-800 text-white font-semibold">Uji SysAdmin</button>
                        </form>
                    </div>
                @endif

                <form action="{{ route('qa.toggle') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 font-semibold">
                        {{ $envMode === 'PRODUCTION' ? 'Aktifkan Mode QA' : 'Kembali ke Mode Produksi' }}
                    </button>
                </form>

                <button type="button" @click="qaModalOpen = true" class="px-2.5 py-1 rounded bg-teal-500 hover:bg-teal-400 text-slate-950 font-extrabold flex items-center gap-1">
                    <i data-lucide="clipboard-check" class="w-3.5 h-3.5"></i>
                    <span>Audit, Uji Wajib & Arsitektur</span>
                </button>
            </div>
        </div>
    </div>

    <!-- HEADER UTAMA JASTIPKAMPUS -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold shadow-sm {{ !$authUser ? 'bg-blue-900' : ($activeWorkspace === 'SYSADMIN_DASHBOARD' ? 'bg-rose-800' : ($activeWorkspace === 'ADMIN_DASHBOARD' ? 'bg-teal-700' : 'bg-blue-900')) }}">
                    <i data-lucide="package" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('public.landing') }}" class="font-extrabold text-lg tracking-tight text-slate-900">
                            {{ $systemConfig->platform_name ?? 'JastipKampus' }}
                        </a>
                        @if($authUser)
                            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase tracking-wider {{ $activeWorkspace === 'SYSADMIN_DASHBOARD' ? 'bg-rose-100 text-rose-900 border border-rose-300' : ($activeWorkspace === 'ADMIN_DASHBOARD' ? 'bg-teal-100 text-teal-900 border border-teal-300' : 'bg-blue-100 text-blue-900 border border-blue-300') }}">
                                {{ $activeWorkspace === 'SYSADMIN_DASHBOARD' ? 'Administrator Utama' : ($activeWorkspace === 'ADMIN_DASHBOARD' ? 'Admin Dashboard' : 'Customer Dashboard') }}
                            </span>
                        @endif
                    </div>
                    <p class="text-[11px] text-slate-500">
                        Platform Jasa Titip Antar Mahasiswa Berbasis Escrow & Lingkungan Akademik
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @if($authUser)
                    @if($authUser->hasRole('CUSTOMER') || $authUser->hasRole('ADMIN'))
                        <a href="{{ route('customer.dashboard') }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-bold border transition {{ $activeWorkspace === 'CUSTOMER_DASHBOARD' ? 'bg-blue-900 text-white border-blue-900' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50' }}">
                            Customer Dashboard
                        </a>
                    @endif

                    @if($authUser->isVerifiedAdmin())
                        <a href="{{ route('admin.dashboard') }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-bold border transition {{ $activeWorkspace === 'ADMIN_DASHBOARD' ? 'bg-teal-700 text-white border-teal-700' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50' }}">
                            Admin Dashboard
                        </a>
                    @endif

                    @if($authUser->hasRole('SYSADMIN'))
                        <a href="{{ route('sysadmin.dashboard') }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-bold border transition {{ $activeWorkspace === 'SYSADMIN_DASHBOARD' ? 'bg-rose-800 text-white border-rose-800' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50' }}">
                            Administrator Utama
                        </a>
                    @endif

                    <div class="h-5 w-px bg-slate-200 mx-1 hidden sm:block"></div>
                    <span class="text-xs font-bold text-slate-700 hidden md:inline">{{ $authUser->name }}</span>

                    <form action="{{ route('auth.logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold flex items-center gap-1">
                            <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                @else
                    <button type="button" @click="$dispatch('open-auth-modal', 'LOGIN')" class="px-3.5 py-1.5 rounded-lg bg-blue-900 hover:bg-blue-800 text-white text-xs font-bold flex items-center gap-1.5">
                        <i data-lucide="log-in" class="w-3.5 h-3.5"></i>
                        <span>Masuk</span>
                    </button>
                    <button type="button" @click="$dispatch('open-auth-modal', 'REGISTER')" class="px-3.5 py-1.5 rounded-lg bg-teal-700 hover:bg-teal-600 text-white text-xs font-bold">
                        Daftar Akun Mahasiswa
                    </button>
                @endif
            </div>
        </div>
    </header>

    <!-- TOAST NOTIFIKASI FLASH SESSION & VALIDATION ERRORS -->
    @if(session('success') || session('error') || session('info') || $errors->any())
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             class="fixed bottom-5 right-5 z-50 max-w-md bg-slate-900 text-white px-4 py-3 rounded-xl shadow-xl border border-slate-700 flex items-center gap-3">
            @if(session('error') || $errors->any())
                <i data-lucide="x-circle" class="w-5 h-5 text-rose-400 shrink-0"></i>
                <span class="text-xs sm:text-sm font-medium">
                    {{ session('error') ?? $errors->first() }}
                </span>
            @else
                <i data-lucide="check-circle-2" class="w-5 h-5 text-teal-400 shrink-0"></i>
                <span class="text-xs sm:text-sm font-medium">
                    {{ session('success') ?? session('info') }}
                </span>
            @endif
            <button type="button" @click="show = false" class="text-slate-400 hover:text-white ml-auto">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    @endif

    <!-- KONTEN UTAMA -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 py-6">
        @yield('content')
    </main>

    <!-- MODAL LAPORAN AUDIT & ARSITEKTUR LARAVEL + MYSQL -->
    <div x-show="qaModalOpen" x-cloak class="fixed inset-0 z-50 bg-slate-900/75 flex items-center justify-center p-4">
        <div @click.away="qaModalOpen = false" class="bg-white border border-slate-200 rounded-2xl max-w-4xl w-full max-h-[88vh] overflow-y-auto p-6 space-y-5 shadow-2xl text-xs">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <div>
                    <span class="font-bold uppercase tracking-wider text-teal-700">Audit Teknis & Arsitektur Konversi</span>
                    <h2 class="text-lg font-extrabold text-slate-900">Pusat Informasi Konversi JastipKampus (Laravel + MySQL)</h2>
                </div>
                <button type="button" @click="qaModalOpen = false" class="px-3.5 py-1.5 rounded-lg bg-slate-900 text-white font-bold">Tutup</button>
            </div>

            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 space-y-2 leading-relaxed">
                <p class="font-extrabold text-sm text-emerald-950">Fitur & Standar Keamanan yang Diterapkan di Laravel + MySQL:</p>
                <ul class="list-disc pl-5 space-y-1 text-slate-700">
                    <li><strong>Autentikasi & Hashing Server-Side:</strong> Menggunakan <code>Hash::make()</code> (Bcrypt), Session Guard Laravel, dan proteksi CSRF di seluruh form.</li>
                    <li><strong>Patokan Harga Customer & Tolak Nego Tanpa Hapus Pesanan:</strong> Customer menetapkan <code>customer_budget</code>; jika nego Admin ditolak, pesanan kembali ke bursa (<code>WAITING_OFFER</code>) agar dapat diambil Admin lain.</li>
                    <li><strong>Simulasi Rekening Bersama (Escrow) & Konfirmasi Selesai oleh Customer:</strong> Dana ditahan di Escrow (<code>HELD_IN_ESCROW</code>) dan hanya cair ke dompet Admin ketika Customer menekan tombol <strong>Pesanan Selesai</strong> di dalam transaksi <code>DB::transaction</code>.</li>
                    <li><strong>Penyimpanan Berkas Fisik KTM Privat & Bukti Foto:</strong> Berkas KTM disimpan di <code>storage/app/private/ktm_vault</code> dengan validasi SHA-256 Checksum dan hanya dapat diakses oleh <code>SYSADMIN</code>.</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
        });
        document.addEventListener('alpine:initialized', () => {
            lucide.createIcons();
        });
    </script>
</body>
</html>