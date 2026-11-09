@extends('layouts.app')

@section('content')
@php
    // Pastikan setiap Program Studi otomatis memiliki Kelas A sampai F di database
    try {
        if (class_exists(\App\Models\AcademicClass::class) && isset($studyPrograms)) {
            $existingMap = [];
            foreach ($classes as $c) {
                $existingMap[$c->study_program_id . '_' . trim($c->name)] = true;
            }
            $createdNew = false;
            foreach ($studyPrograms as $prog) {
                foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $letter) {
                    $targetName = trim($prog->name) . ' ' . $letter;
                    if (!isset($existingMap[$prog->id . '_' . $targetName])) {
                        \App\Models\AcademicClass::firstOrCreate(
                            ['study_program_id' => $prog->id, 'name' => $targetName],
                            ['cohort_year' => (int) date('Y')]
                        );
                        $createdNew = true;
                    }
                }
            }
            if ($createdNew) {
                $classes = \App\Models\AcademicClass::orderBy('name')->get();
            }
        }
    } catch (\Throwable $e) {
        // Abaikan jika koneksi sedang dalam mode baca saja
    }
@endphp

<div x-data="{
        authModalOpen: {{ $errors->any() ? 'true' : 'false' }},
        authMode: 'LOGIN',
        selectedFaculty: '',
        selectedProgram: '',
        selectedClass: '',
        programs: {{ Js::from($studyPrograms) }},
        classes: {{ Js::from($classes) }},
        get filteredPrograms() {
            return this.programs.filter(p => String(p.faculty_id) === String(this.selectedFaculty));
        },
        get filteredClasses() {
            return this.classes
                .filter(c => String(c.study_program_id) === String(this.selectedProgram))
                .sort((a, b) => a.name.localeCompare(b.name));
        }
    }"
    @open-auth-modal.window="authMode = $event.detail; authModalOpen = true"
    class="space-y-8 py-4">

    <!-- HERO SECTION -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-10 shadow-sm grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
        <div class="lg:col-span-7 space-y-4">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-blue-900 border border-blue-200 text-xs font-extrabold">
                <i data-lucide="shield-check" class="w-4 h-4 text-teal-600"></i>
                <span>Platform Jasa Titip Mahasiswa Berbasis Escrow & Verifikasi KTM</span>
            </span>
            <h1 class="text-2xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight">
                Urus Keperluan Kampus dari Luar Kota dengan Rekening Bersama (Escrow)
            </h1>
            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                JastipKampus mempertemukan mahasiswa yang membutuhkan bantuan pengambilan KTM, dokumen
                akademik, pencetakan tugas, atau pembelian perlengkapan kuliah dengan mahasiswa penyedia
                layanan terverifikasi. Dilengkapi fitur Patokan Harga Customer, Rekening Bersama (Escrow), Chat In-App, dan Bukti Foto.
            </p>
            <div class="flex flex-wrap gap-2.5 sm:gap-3 pt-2">
                <button type="button" @click="authMode = 'LOGIN'; authModalOpen = true"
                        class="px-4 sm:px-5 py-2.5 rounded-xl bg-blue-900 hover:bg-blue-800 text-white text-xs sm:text-sm font-bold flex items-center gap-2">
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    <span>Masuk ke Akun</span>
                </button>
                <button type="button" @click="authMode = 'REGISTER'; authModalOpen = true"
                        class="px-4 sm:px-5 py-2.5 rounded-xl bg-teal-700 hover:bg-teal-600 text-white text-xs sm:text-sm font-bold">
                    Daftar Akun Customer Baru
                </button>
                <button type="button" @click="authMode = 'SYSADMIN_LOGIN'; authModalOpen = true"
                        class="px-4 py-2.5 rounded-xl bg-rose-800 hover:bg-rose-700 text-white text-xs sm:text-sm font-bold flex items-center gap-1.5">
                    <i data-lucide="shield-alert" class="w-4 h-4"></i>
                    <span>Login Admin Utama (SysAdmin)</span>
                </button>
                @if(!$hasSysAdmin)
                    <button type="button" @click="authMode = 'SYSADMIN_INIT'; authModalOpen = true"
                            class="px-4 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-900 border border-rose-300 text-xs font-bold">
                        Setup Awal Administrator Utama
                    </button>
                @endif
            </div>
        </div>

        <div class="lg:col-span-5 bg-slate-50 border border-slate-200 rounded-2xl p-5 space-y-3 text-xs">
            <h2 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                <i data-lucide="lock" class="w-4 h-4 text-blue-900"></i>
                <span>Standar Keamanan & Perlindungan Transaksi</span>
            </h2>
            <ul class="space-y-2 text-slate-600">
                <li class="flex items-start gap-2">
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-teal-600 shrink-0 mt-0.5"></i>
                    <span><strong>Rekening Bersama (Escrow):</strong> Dana ditahan sistem dan baru diteruskan ke saldo Admin setelah Customer mengonfirmasi pesanan selesai.</span>
                </li>
                <li class="flex items-start gap-2">
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-teal-600 shrink-0 mt-0.5"></i>
                    <span><strong>Patokan Harga & Bursa Terbuka:</strong> Customer bebas menentukan patokan harga awal; jika nego ditolak, pesanan tetap terbuka bagi Admin lain.</span>
                </li>
                <li class="flex items-start gap-2">
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-teal-600 shrink-0 mt-0.5"></i>
                    <span><strong>Chat In-App & Bukti Foto:</strong> Diskusi langsung di dalam pesanan serta lampiran bukti foto struk dan penyerahan barang.</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- KATALOG LAYANAN AKTIF PUBLIK -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
        <div>
            <h2 class="text-lg font-extrabold text-slate-900">Layanan Aktif Saat Ini</h2>
            <p class="text-xs text-slate-500">Silakan login terlebih dahulu untuk membuat pesanan atau mengajukan permintaan jastip bebas</p>
        </div>

        @if($services->isEmpty())
            <div class="border-2 border-dashed border-slate-300 rounded-2xl p-10 text-center space-y-2 bg-slate-50">
                <i data-lucide="package" class="w-10 h-10 text-slate-400 mx-auto"></i>
                <h3 class="text-base font-extrabold text-slate-900">Belum ada layanan JastipKampus yang tersedia saat ini.</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto">
                    Layanan akan muncul di sini setelah Admin yang terverifikasi membuat dan memublikasikannya.
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($services as $srv)
                    <div class="p-4 rounded-xl border border-slate-200 flex flex-col justify-between gap-3">
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-900 text-[11px] font-bold">
                                    {{ $srv->category?->name ?? 'Layanan Mahasiswa' }}
                                </span>
                                <span class="text-sm font-extrabold text-teal-700">
                                    {{ \App\Helpers\FormatHelper::formatIDR($srv->price) }}
                                </span>
                            </div>
                            <h3 class="font-bold text-sm text-slate-900">{{ $srv->name }}</h3>
                            <p class="text-xs text-slate-600">{{ $srv->description }}</p>
                            <p class="text-[11px] text-slate-500 pt-1">
                                Penyedia: <strong>{{ $srv->ownerAdmin?->name }}</strong>
                                ({{ $srv->faculty?->name ?? '-' }} • {{ $srv->studyProgram?->name ?? '-' }}{{ $srv->show_class_publicly && $srv->academicClass ? ' • ' . $srv->academicClass->name : '' }})
                            </p>
                        </div>
                        <button type="button" @click="authMode = 'LOGIN'; authModalOpen = true"
                                class="w-full py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold">
                            Login untuk Memesan Layanan
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- MODAL LOGIN, REGISTRASI CUSTOMER, & LOGIN/SETUP SYSADMIN -->
    <div x-show="authModalOpen" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 flex items-center justify-center p-4">
        <div @click.away="authModalOpen = false" class="bg-white border border-slate-200 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl text-xs max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-extrabold text-base text-slate-900"
                    x-text="authMode === 'LOGIN' ? 'Login Terverifikasi JastipKampus' : (authMode === 'REGISTER' ? 'Registrasi Akun Mahasiswa (Peran CUSTOMER)' : (authMode === 'SYSADMIN_LOGIN' ? 'Login Administrator Utama (SysAdmin)' : 'Inisialisasi Aman Administrator Utama Pertama'))">
                </h3>
                <button type="button" @click="authModalOpen = false" class="text-slate-400 hover:text-slate-700">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- FORM LOGIN UMUM -->
            <form x-show="authMode === 'LOGIN'" action="{{ route('auth.login') }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block font-bold mb-1">Email Akun *</label>
                    <input type="email" name="email" required value="{{ old('email') }}" placeholder="nama@kampus.ac.id" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
                <div>
                    <label class="block font-bold mb-1">Kata Sandi *</label>
                    <input type="password" name="password" required placeholder="Masukkan kata sandi Anda" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
                <button type="submit" class="w-full py-2.5 rounded-xl bg-blue-900 hover:bg-blue-800 text-white font-bold">
                    Masuk dengan Verifikasi Kredensial
                </button>
                <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="authMode = 'REGISTER'" class="text-teal-700 font-bold hover:underline">
                        Belum punya akun? Daftar Customer
                    </button>
                    <button type="button" @click="authMode = 'SYSADMIN_LOGIN'" class="text-rose-700 font-bold hover:underline">
                        Login Admin Utama (SysAdmin)
                    </button>
                </div>
            </form>

            <!-- FORM KHUSUS LOGIN ADMIN UTAMA / SYSADMIN -->
            <div x-show="authMode === 'SYSADMIN_LOGIN'" class="space-y-3">
                <div class="p-2.5 rounded-lg bg-rose-50 text-rose-900 border border-rose-200">
                    Masuk ke panel <strong>Administrator Utama (SysAdmin)</strong> untuk memverifikasi pengajuan KTM Admin dan memantau transaksi Escrow.
                </div>
                <form action="{{ route('auth.login') }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block font-bold mb-1">Email Administrator Utama *</label>
                        <input type="email" name="email" required value="{{ old('email', 'sysadmin@jastipkampus.ac.id') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                    </div>
                    <div>
                        <label class="block font-bold mb-1">Kata Sandi SysAdmin *</label>
                        <input type="password" name="password" required placeholder="Masukkan kata sandi SysAdmin" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                    </div>
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-rose-800 hover:bg-rose-700 text-white font-bold">
                        Masuk sebagai Administrator Utama
                    </button>
                </form>

                <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                    <button type="button" @click="authMode = 'LOGIN'" class="text-slate-600 font-bold hover:underline">
                        Kembali ke Login Mahasiswa
                    </button>
                    @if(!$hasSysAdmin)
                        <button type="button" @click="authMode = 'SYSADMIN_INIT'" class="text-rose-700 font-bold hover:underline">
                            Setup Awal SysAdmin Baru
                        </button>
                    @endif
                </div>
            </div>

            <!-- FORM REGISTRASI CUSTOMER (KELAS A SAMPAI F TANPA TAHUN) -->
            <form x-show="authMode === 'REGISTER'" action="{{ route('auth.register') }}" method="POST" class="space-y-2.5">
                @csrf
                <div class="p-2.5 rounded-lg bg-blue-50 text-blue-900">
                    Setiap pendaftaran baru mendapatkan peran <strong>CUSTOMER</strong> secara default.
                </div>
                <div>
                    <label class="block font-bold mb-1">Nama Lengkap *</label>
                    <input type="text" name="name" required value="{{ old('name') }}" class="w-full px-3 py-1.5 rounded-lg border border-slate-300">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <label class="block font-bold mb-1">Email *</label>
                        <input type="email" name="email" required value="{{ old('email') }}" class="w-full px-3 py-1.5 rounded-lg border border-slate-300">
                    </div>
                    <div>
                        <label class="block font-bold mb-1">Kata Sandi (Min. 8) *</label>
                        <input type="password" name="password" required minlength="8" class="w-full px-3 py-1.5 rounded-lg border border-slate-300">
                    </div>
                </div>
                <div>
                    <label class="block font-bold mb-1">Universitas *</label>
                    <input type="text" name="university" value="Universitas Nusantara" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300">
                </div>
                <div>
                    <label class="block font-bold mb-1">1. Pilih Fakultas *</label>
                    <select name="faculty_id" x-model="selectedFaculty" @change="selectedProgram = ''; selectedClass = ''" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 bg-white font-semibold">
                        <option value="">-- Pilih Fakultas --</option>
                        @foreach($faculties as $fac)
                            <option value="{{ $fac->id }}">{{ $fac->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-bold mb-1">2. Pilih Program Studi *</label>
                    <select name="study_program_id" x-model="selectedProgram" @change="selectedClass = ''" :disabled="!selectedFaculty" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 bg-white font-semibold">
                        <option value="">-- Pilih Program Studi --</option>
                        <template x-for="prd in filteredPrograms" :key="prd.id">
                            <option :value="prd.id" x-text="prd.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block font-bold mb-1">3. Pilih Kelas *</label>
                    <select name="class_id" x-model="selectedClass" :disabled="!selectedProgram" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 bg-white font-semibold">
                        <option value="">-- Pilih Kelas --</option>
                        <template x-for="cls in filteredClasses" :key="cls.id">
                            <option :value="cls.id" x-text="cls.name"></option>
                        </template>
                    </select>
                </div>
                <button type="submit" class="w-full py-2.5 rounded-xl bg-teal-700 hover:bg-teal-600 text-white font-bold">
                    Daftarkan Akun Customer
                </button>
                <button type="button" @click="authMode = 'LOGIN'" class="w-full text-center text-slate-600 font-bold hover:underline pt-1">
                    Sudah punya akun? Kembali ke Login
                </button>
            </form>

            <!-- FORM SETUP SYSADMIN PERTAMA -->
            @if(!$hasSysAdmin)
                <form x-show="authMode === 'SYSADMIN_INIT'" action="{{ route('auth.init.sysadmin') }}" method="POST" class="space-y-3">
                    @csrf
                    <div class="p-2.5 rounded-lg bg-rose-50 text-rose-900 border border-rose-200">
                        Inisialisasi satu kali untuk membuat Administrator Utama pertama tanpa kredensial bawaan publik.
                    </div>
                    <input type="text" name="name" required placeholder="Nama Lengkap Administrator Utama" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                    <input type="email" name="email" required placeholder="Email Administrator Utama" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                    <input type="password" name="password" required minlength="10" placeholder="Kata Sandi Baru (Min. 10 karakter)" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-rose-800 text-white font-bold">
                        Inisialisasi Administrator Utama
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection