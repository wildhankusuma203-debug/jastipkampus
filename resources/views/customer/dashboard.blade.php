@extends('layouts.app')

@section('content')
@php
    $orderStatusMap = \App\Helpers\FormatHelper::orderStatusMap();
    $escrowStatusMap = \App\Helpers\FormatHelper::escrowStatusMap();
    $verifStatusMap = \App\Helpers\FormatHelper::verificationStatusMap();

    $navItems = [
        ['id' => 'CUST_HOME', 'label' => 'Dashboard', 'icon' => 'layout-dashboard', 'badge' => 0],
        ['id' => 'CUST_EXPLORE', 'label' => 'Jelajahi Layanan', 'icon' => 'search', 'badge' => 0],
        ['id' => 'CUST_ORDER_SERVICE', 'label' => 'Pesan Layanan Admin', 'icon' => 'shopping-bag', 'badge' => 0],
        ['id' => 'CUST_CREATE_REQUEST', 'label' => 'Buat Permintaan Jastip', 'icon' => 'plus-circle', 'badge' => 0],
        ['id' => 'CUST_MY_ORDERS', 'label' => 'Pesanan Saya', 'icon' => 'package', 'badge' => $myActiveOrders->count()],
        ['id' => 'CUST_PRICE_OFFERS', 'label' => 'Penawaran Harga', 'icon' => 'tag', 'badge' => $myPendingOffers->count()],
        ['id' => 'CUST_ORDER_HISTORY', 'label' => 'Riwayat Pesanan', 'icon' => 'history', 'badge' => 0],
        ['id' => 'CUST_PROFILE', 'label' => 'Profil Saya', 'icon' => 'user', 'badge' => 0],
        ['id' => 'CUST_NOTIFICATIONS', 'label' => 'Notifikasi Baru', 'icon' => 'bell', 'badge' => $notifications->count()],
        ['id' => 'CUST_REGISTER_ADMIN', 'label' => 'Daftar sebagai Admin', 'icon' => 'shield-check', 'badge' => 0],
    ];
@endphp

<div x-data="{
        escrowOrderId: null,
        escrowOrderTitle: '',
        escrowOrderPrice: '',
        counterOfferOrderId: null,
        chatOrderId: {{ session('open_chat_order_id') ? (int) session('open_chat_order_id') : 'null' }},
        reportTarget: null,
        profFaculty: '{{ $currentUser->faculty_id }}',
        profProgram: '{{ $currentUser->study_program_id }}',
        profClass: '{{ $currentUser->class_id }}',
        programs: {{ Js::from($studyPrograms) }},
        classes: {{ Js::from($classes) }},
        get filteredProfPrograms() {
            return this.programs.filter(p => String(p.faculty_id) === String(this.profFaculty));
        },
        get filteredProfClasses() {
            return this.classes.filter(c => String(c.study_program_id) === String(this.profProgram));
        }
    }"
    class="grid grid-cols-1 lg:grid-cols-12 gap-6">

    <!-- SIDEBAR MENU CUSTOMER -->
    <aside class="lg:col-span-3 space-y-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-4 space-y-3 shadow-sm">
            <div class="p-3 rounded-xl bg-blue-950 text-white space-y-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-teal-300">Customer Dashboard</span>
                <p class="font-extrabold text-sm truncate">{{ $currentUser->name }}</p>
                <p class="text-[11px] text-slate-300 truncate">{{ $currentUser->faculty?->name ?? 'Belum Ditentukan' }}</p>
                <p class="text-[11px] text-teal-300 font-semibold truncate">
                    {{ $currentUser->studyProgram?->name ?? '-' }} • {{ $currentUser->academicClass?->name ?? '-' }}
                </p>
            </div>

            <nav class="space-y-1">
                @foreach($navItems as $item)
                    @php $isActive = $customerMenu === $item['id']; @endphp
                    <a href="{{ route('customer.dashboard', ['menu' => $item['id']]) }}"
                       class="w-full px-3 py-2.5 rounded-xl text-xs font-bold flex items-center justify-between transition {{ $isActive ? 'bg-blue-900 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span class="flex items-center gap-2.5">
                            <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 shrink-0"></i>
                            <span>{{ $item['label'] }}</span>
                        </span>
                        @if($item['badge'] > 0)
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $isActive ? 'bg-teal-400 text-slate-950' : 'bg-blue-100 text-blue-900' }}">
                                {{ $item['badge'] }}
                            </span>
                        @endif
                    </a>
                @endforeach

                @if($currentUser->isVerifiedAdmin())
                    <a href="{{ route('admin.dashboard') }}"
                       class="w-full mt-2 px-3 py-2.5 rounded-xl text-xs font-bold bg-teal-50 text-teal-900 border border-teal-300 hover:bg-teal-100 flex items-center gap-2">
                        <i data-lucide="briefcase" class="w-4 h-4 text-teal-700"></i>
                        <span>Buka Admin Dashboard</span>
                    </a>
                @endif
            </nav>
        </div>
    </aside>

    <!-- AREA KONTEN CUSTOMER -->
    <section class="lg:col-span-9 space-y-6">

        <!-- 1. BERANDA CUSTOMER -->
        @if($customerMenu === 'CUST_HOME')
            <div class="space-y-6">
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                        <div>
                            <span class="text-xs font-bold text-teal-700 uppercase tracking-wider">Beranda Mahasiswa UPNYK</span>
                            <h1 class="text-2xl font-extrabold text-slate-900 mt-0.5">Halo, {{ $currentUser->name }}!</h1>
                            <p class="text-xs text-slate-600 mt-1">
                                Lingkungan Akademik: <strong>{{ $currentUser->faculty?->name }}</strong> •
                                Program Studi <strong>{{ $currentUser->studyProgram?->name }}</strong> •
                                Kelas <strong>{{ $currentUser->academicClass?->name }}</strong>
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('customer.dashboard', ['menu' => 'CUST_ORDER_SERVICE']) }}"
                               class="px-4 py-2.5 rounded-xl bg-teal-700 hover:bg-teal-600 text-white text-xs font-bold flex items-center gap-2 shrink-0">
                                <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                                <span>Pesan Layanan Admin</span>
                            </a>
                            <a href="{{ route('customer.dashboard', ['menu' => 'CUST_CREATE_REQUEST']) }}"
                               class="px-4 py-2.5 rounded-xl bg-blue-900 hover:bg-blue-800 text-white text-xs font-bold flex items-center gap-2 shrink-0">
                                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                                <span>Buat Permintaan Bebas</span>
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="p-4 rounded-xl bg-blue-50/70 border border-blue-200">
                            <span class="text-xs text-blue-900 font-semibold">Pesanan Aktif</span>
                            <p class="text-2xl font-extrabold text-blue-950 mt-1">{{ $myActiveOrders->count() }}</p>
                        </div>
                        <div class="p-4 rounded-xl bg-emerald-50/70 border border-emerald-200">
                            <span class="text-xs text-emerald-900 font-semibold">Pesanan Selesai</span>
                            <p class="text-2xl font-extrabold text-emerald-950 mt-1">{{ $myCompletedOrders->count() }}</p>
                        </div>
                        <div class="p-4 rounded-xl bg-amber-50/70 border border-amber-200">
                            <span class="text-xs text-amber-900 font-semibold">Penawaran Nego Menunggu</span>
                            <p class="text-2xl font-extrabold text-amber-950 mt-1">{{ $myPendingOffers->count() }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-extrabold text-slate-900">Layanan Jastip Tersedia</h2>
                            <p class="text-xs text-slate-500">Menampilkan layanan resmi yang aktif dan dipublikasikan oleh Admin</p>
                        </div>
                        <a href="{{ route('customer.dashboard', ['menu' => 'CUST_EXPLORE']) }}" class="text-xs font-bold text-blue-900 hover:underline">
                            Cari & Filter Layanan →
                        </a>
                    </div>

                    @if($allActiveServices->isEmpty())
                        <div class="border-2 border-dashed border-slate-300 rounded-2xl p-10 text-center space-y-2 bg-slate-50">
                            <i data-lucide="package" class="w-10 h-10 text-slate-400 mx-auto"></i>
                            <h3 class="text-base font-extrabold text-slate-900">Belum ada layanan JastipKampus yang tersedia saat ini.</h3>
                            <p class="text-xs text-slate-500 max-w-md mx-auto">
                                Anda tetap dapat mengajukan permintaan jastip bebas dengan menentukan patokan harga sendiri melalui menu Buat Permintaan Jastip.
                            </p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($allActiveServices->take(4) as $srv)
                                <div class="p-4 rounded-xl border border-slate-200 hover:border-blue-800 transition flex flex-col justify-between gap-3">
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-900 text-[11px] font-bold">{{ $srv->category?->name }}</span>
                                            <span class="text-sm font-extrabold text-teal-700">{{ \App\Helpers\FormatHelper::formatIDR($srv->price) }}</span>
                                        </div>
                                        <h3 class="font-bold text-sm text-slate-900">{{ $srv->name }}</h3>
                                        <p class="text-xs text-slate-600 line-clamp-2">{{ $srv->description }}</p>
                                        <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-100 text-[11px] text-slate-600 space-y-1">
                                            <p>Penyedia: <strong class="text-slate-900">{{ $srv->ownerAdmin?->name }}</strong> ({{ $srv->faculty?->name }} • {{ $srv->studyProgram?->name }})</p>
                                            @if($srv->requirement_file_path)
                                                <a href="{{ asset('storage/' . $srv->requirement_file_path) }}" target="_blank" class="inline-flex items-center gap-1 text-blue-800 font-bold underline">
                                                    <i data-lucide="file-text" class="w-3 h-3"></i>
                                                    <span>Unduh File Syarat / Contoh Format (PDF)</span>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex justify-end pt-2 border-t border-slate-100">
                                        <a href="{{ route('customer.dashboard', ['menu' => 'CUST_ORDER_SERVICE', 'service_id' => $srv->id]) }}"
                                           class="px-3.5 py-1.5 rounded-lg bg-teal-700 hover:bg-teal-600 text-white text-xs font-bold">
                                            Gunakan Jasa Ini
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- 2. JELAJAHI LAYANAN -->
        @if($customerMenu === 'CUST_EXPLORE')
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-5">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900">Jelajahi Layanan Jastip Mahasiswa</h2>
                    <p class="text-xs text-slate-500">Pilih layanan resmi dari Admin berbagai Fakultas dan Program Studi UPN "Veteran" Yogyakarta.</p>
                </div>

                <form action="{{ route('customer.dashboard') }}" method="GET" class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3 text-xs">
                    <input type="hidden" name="menu" value="CUST_EXPLORE">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="md:col-span-2">
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama layanan, deskripsi, kategori, penyedia..." class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white">
                        </div>
                        <div>
                            <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Batas Harga Maksimal (Rp)" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <select name="faculty_id" class="px-2.5 py-2 rounded-lg border border-slate-300 bg-white font-semibold">
                            <option value="ALL">Semua Fakultas</option>
                            @foreach($faculties as $f)
                                <option value="{{ $f->id }}" {{ request('faculty_id') == $f->id ? 'selected' : '' }}>{{ $f->name }}</option>
                            @endforeach
                        </select>
                        <select name="study_program_id" class="px-2.5 py-2 rounded-lg border border-slate-300 bg-white font-semibold">
                            <option value="ALL">Semua Program Studi</option>
                            @foreach($studyPrograms as $p)
                                <option value="{{ $p->id }}" {{ request('study_program_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                        <select name="category_id" class="px-2.5 py-2 rounded-lg border border-slate-300 bg-white font-semibold">
                            <option value="ALL">Semua Kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-900 text-white font-bold">Terapkan Filter</button>
                    </div>
                </form>

                @if($filteredServices->isEmpty())
                    <div class="p-8 text-center border border-slate-200 rounded-xl">
                        <p class="font-bold text-sm text-slate-800">Tidak ada layanan aktif yang ditemukan.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($filteredServices as $srv)
                            <div class="p-5 rounded-2xl border border-slate-200 flex flex-col justify-between gap-4 hover:border-teal-700 transition">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="px-2.5 py-0.5 rounded bg-blue-50 text-blue-900 text-xs font-bold">{{ $srv->category?->name }}</span>
                                        <span class="text-base font-extrabold text-teal-700">{{ \App\Helpers\FormatHelper::formatIDR($srv->price) }}</span>
                                    </div>
                                    <h3 class="font-extrabold text-base text-slate-900">{{ $srv->name }}</h3>
                                    <p class="text-xs text-slate-600">{{ $srv->description }}</p>
                                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-1">
                                        <p class="font-bold text-slate-900">Penyedia: {{ $srv->ownerAdmin?->name }}</p>
                                        <p class="text-slate-600">{{ $srv->faculty?->name }} • {{ $srv->studyProgram?->name }}</p>
                                        <p class="text-[11px] text-slate-500">Syarat: {{ $srv->requirements ?: 'Sesuai prosedur kampus' }}</p>
                                        @if($srv->requirement_file_path)
                                            <div class="pt-1">
                                                <a href="{{ asset('storage/' . $srv->requirement_file_path) }}" target="_blank"
                                                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-blue-100 text-blue-900 font-bold text-[11px]">
                                                    <i data-lucide="download" class="w-3 h-3"></i>
                                                    <span>Unduh Contoh Syarat / Dokumen Admin (PDF)</span>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                                    <button type="button" @click="reportTarget = '{{ addslashes($srv->name) }}'" class="text-[11px] text-slate-500 hover:text-rose-600 font-semibold flex items-center gap-1">
                                        <i data-lucide="flag" class="w-3 h-3"></i>
                                        <span>Laporkan Masalah</span>
                                    </button>
                                    <a href="{{ route('customer.dashboard', ['menu' => 'CUST_ORDER_SERVICE', 'service_id' => $srv->id]) }}"
                                       class="px-4 py-2 rounded-xl bg-teal-700 hover:bg-teal-600 text-white text-xs font-bold">
                                        Gunakan Layanan Ini
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <!-- 2B. MENU KHUSUS MEMESAN LAYANAN RESMI ADMIN -->
        @if($customerMenu === 'CUST_ORDER_SERVICE')
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-5">
                <div class="border-b border-slate-100 pb-4">
                    <span class="px-2.5 py-0.5 rounded bg-teal-100 text-teal-900 text-[11px] font-extrabold uppercase">Pemesanan Layanan Resmi Admin</span>
                    <h2 class="text-xl font-extrabold text-slate-900 mt-1">Formulir Penggunaan Jasa Layanan Admin</h2>
                    <p class="text-xs text-slate-500">
                        Menu ini khusus untuk memesan layanan yang telah dipublikasikan oleh Admin dengan tarif tetap.
                    </p>
                </div>

                @if($allActiveServices->isEmpty())
                    <div class="p-8 text-center bg-slate-50 rounded-xl border border-dashed border-slate-300 space-y-2">
                        <p class="font-bold text-sm text-slate-800">Belum ada layanan Admin yang aktif saat ini.</p>
                        <a href="{{ route('customer.dashboard', ['menu' => 'CUST_CREATE_REQUEST']) }}" class="inline-block px-4 py-2 rounded-lg bg-blue-900 text-white text-xs font-bold">
                            Buat Permintaan Jastip Bebas Saja
                        </a>
                    </div>
                @else
                    <div class="p-4 rounded-xl bg-amber-50 border border-amber-300 text-amber-950 space-y-1 text-xs">
                        <p class="font-extrabold flex items-center gap-1.5 text-amber-900">
                            <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0"></i>
                            <span>PERINGATAN PENTING PEMBAYARAN ESCROW:</span>
                        </p>
                        <p>
                            Setelah mengisi formulir ini, Anda <strong>wajib melakukan pembayaran ke Rekening Bersama (Escrow) terlebih dahulu</strong> agar pesanan dapat diproses oleh Admin. <strong>Perhatian: Ketika Anda sudah membayar, pesanan terkunci dan TIDAK DAPAT DIBATALKAN.</strong>
                        </p>
                    </div>

                    <form action="{{ route('customer.orders.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs sm:text-sm">
                        @csrf
                        <div>
                            <label class="block font-bold text-slate-800 mb-1">Pilih Layanan Resmi Admin *</label>
                            <select name="service_id" required
                                    onchange="window.location.href='{{ route('customer.dashboard') }}?menu=CUST_ORDER_SERVICE&service_id=' + this.value"
                                    class="w-full px-3 py-2.5 rounded-lg border border-teal-400 bg-teal-50/50 font-extrabold text-slate-900">
                                @foreach($allActiveServices as $s)
                                    <option value="{{ $s->id }}" {{ $selectedServiceObj && $selectedServiceObj->id === $s->id ? 'selected' : '' }}>
                                        {{ $s->name }} — Tarif Tetap: {{ \App\Helpers\FormatHelper::formatIDR($s->price) }} (Penyedia: {{ $s->ownerAdmin?->name }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if($selectedServiceObj)
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="font-extrabold text-slate-900">Detail Layanan Terpilih: {{ $selectedServiceObj->name }}</span>
                                    <span class="font-extrabold text-base text-teal-700">{{ \App\Helpers\FormatHelper::formatIDR($selectedServiceObj->price) }}</span>
                                </div>
                                <p class="text-slate-600">{{ $selectedServiceObj->description }}</p>
                                <p class="text-slate-700"><strong>Persyaratan dari Admin:</strong> {{ $selectedServiceObj->requirements ?: 'Tidak ada syarat khusus' }}</p>
                                @if($selectedServiceObj->requirement_file_path)
                                    <div class="pt-1">
                                        <a href="{{ asset('storage/' . $selectedServiceObj->requirement_file_path) }}" target="_blank"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-900 text-white font-bold">
                                            <i data-lucide="file-down" class="w-3.5 h-3.5"></i>
                                            <span>Unduh File Syarat / Contoh Format dari Admin (PDF)</span>
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div>
                            <label class="block font-bold text-slate-800 mb-1">Judul Pesanan Layanan *</label>
                            <input type="text" name="title" required
                                   value="{{ old('title', $selectedServiceObj ? 'Pesan Layanan: ' . $selectedServiceObj->name : '') }}"
                                   class="w-full px-3 py-2 rounded-lg border border-slate-300">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-800 mb-1">Rincian Kebutuhan / Instruksi untuk Admin *</label>
                            <textarea name="description" rows="3" required placeholder="Tuliskan detail instruksi untuk layanan yang Anda pilih..." class="w-full px-3 py-2 rounded-lg border border-slate-300">{{ old('description') }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-slate-800 mb-1">Rincian Barang atau Dokumen *</label>
                                <input type="text" name="items_detail" required value="{{ old('items_detail') }}" placeholder="Contoh: 1 Berkas Transkrip / 1 Paket Modul" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-800 mb-1">Tenggat Waktu *</label>
                                <input type="datetime-local" name="deadline" required value="{{ old('deadline', now()->addDay()->format('Y-m-d\TH:i')) }}" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-slate-800 mb-1">Lokasi Pengambilan *</label>
                                <input type="text" name="pickup_location" required value="{{ old('pickup_location') }}" placeholder="Contoh: Gedung Fakultas / Kampus UPNYK" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-800 mb-1">Lokasi Penyerahan *</label>
                                <input type="text" name="delivery_location" required value="{{ old('delivery_location') }}" placeholder="Contoh: Kos Babarsari / Kirim Ekspedisi" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                            </div>
                        </div>

                        <div class="p-4 rounded-xl bg-blue-50/60 border border-blue-200 space-y-2 text-xs">
                            <label class="block font-extrabold text-blue-950">
                                Unggah File Lampiran / Surat Kuasa / Dokumen yang Akan Di-print (Format PDF, Maks 5MB)
                            </label>
                            <input type="file" name="supporting_file" accept="application/pdf,.pdf" class="block w-full text-xs">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-slate-800 mb-1">Catatan Tambahan</label>
                                <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Contoh: Hubungi via Chat In-App" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-800 mb-1">Keterangan Pendukung</label>
                                <input type="text" name="supporting_info" value="{{ old('supporting_info') }}" placeholder="Contoh: File PDF sudah dilampirkan" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                            </div>
                        </div>

                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-teal-700 hover:bg-teal-600 text-white font-extrabold text-xs sm:text-sm">
                            Buat Pesanan Layanan & Lanjut ke Pembayaran Escrow
                        </button>
                    </form>
                @endif
            </div>
        @endif

        <!-- 3. FORMULIR PERMINTAAN JASTIP BEBAS (TANPA MINIMAL KARAKTER JUDUL & DESKRIPSI) -->
        @if($customerMenu === 'CUST_CREATE_REQUEST')
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <span class="px-2.5 py-0.5 rounded bg-blue-100 text-blue-900 text-[11px] font-extrabold uppercase">Bursa Permintaan Jastip Bebas</span>
                    <h2 class="text-xl font-extrabold text-slate-900 mt-1">Buat Permintaan Jastip Bebas (Pasang Patokan Harga Sendiri)</h2>
                    <p class="text-xs text-slate-500">
                        Gunakan menu ini jika kebutuhan Anda belum tersedia di katalog layanan Admin. Anda bebas menentukan patokan harga awal dan mengunggah file PDF pendukung.
                    </p>
                </div>

                <form action="{{ route('customer.orders.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs sm:text-sm">
                    @csrf
                    <input type="hidden" name="service_id" value="CUSTOM">

                    <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 space-y-1.5">
                        <label class="block font-extrabold text-amber-950">Patokan Harga / Budget Awal Anda (IDR) *</label>
                        <input type="number" name="customer_budget" required value="{{ old('customer_budget', 15000) }}" min="1000"
                               class="w-full px-3 py-2 rounded-lg border border-amber-300 bg-white font-extrabold text-teal-800">
                        <p class="text-[11px] text-amber-900">
                            Jika Admin merasa patokan harga ini terlalu rendah dan mengajukan nego, Anda nantinya dapat memilih <strong>Setujui Harga</strong>, <strong>Nego Lagi (Ajukan Harga Balik)</strong>, atau <strong>Tolak Nego</strong>.
                        </p>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-800 mb-1">Judul Permintaan *</label>
                        <input type="text" name="title" required value="{{ old('title') }}" placeholder="Contoh: Print laporan / Ambil KTM" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-800 mb-1">Deskripsi Kebutuhan *</label>
                        <textarea name="description" rows="3" required placeholder="Jelaskan kebutuhan Anda..." class="w-full px-3 py-2 rounded-lg border border-slate-300">{{ old('description') }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-800 mb-1">Rincian Barang atau Dokumen *</label>
                            <input type="text" name="items_detail" required value="{{ old('items_detail') }}" placeholder="Contoh: 1 Dokumen Laporan PDF / 1 KTM Asli" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-800 mb-1">Tenggat Waktu *</label>
                            <input type="datetime-local" name="deadline" required value="{{ old('deadline', now()->addDay()->format('Y-m-d\TH:i')) }}" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-800 mb-1">Lokasi Pengambilan *</label>
                            <input type="text" name="pickup_location" required value="{{ old('pickup_location') }}" placeholder="Contoh: Fotokopi sekitar kampus / Gedung Rektorat UPNYK" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-800 mb-1">Lokasi Penyerahan *</label>
                            <input type="text" name="delivery_location" required value="{{ old('delivery_location') }}" placeholder="Contoh: Lobby Fakultas Teknik Industri / Kos Seturan" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-blue-50/70 border border-blue-200 space-y-2 text-xs">
                        <label class="block font-extrabold text-blue-950">
                            Unggah File Dokumen / Surat Pengambilan / File yang Akan Di-print (Format PDF, Maks 5MB)
                        </label>
                        <input type="file" name="supporting_file" accept="application/pdf,.pdf" class="block w-full text-xs">
                        <p class="text-[11px] text-slate-600">
                            Lampirkan file PDF Anda apabila Admin harus menunjukkan surat syarat pengambilan barang atau mencetak (*print*) dokumen tugas/skripsi Anda.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-800 mb-1">Catatan Tambahan</label>
                            <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Contoh: Jilid lakban warna biru" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-800 mb-1">Informasi Pendukung</label>
                            <input type="text" name="supporting_info" value="{{ old('supporting_info') }}" placeholder="Contoh: File PDF sudah dilampirkan di atas" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                        </div>
                    </div>

                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-900 hover:bg-blue-800 text-white font-bold text-xs sm:text-sm">
                        Publikasikan Permintaan Jastip ke Bursa Admin
                    </button>
                </form>
            </div>
        @endif

        <!-- 4. PESANAN SAYA & 6. RIWAYAT PESANAN -->
        @if($customerMenu === 'CUST_MY_ORDERS' || $customerMenu === 'CUST_ORDER_HISTORY')
            @php $listOrders = $customerMenu === 'CUST_MY_ORDERS' ? $myActiveOrders : $myOrders; @endphp
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                <h2 class="text-xl font-extrabold text-slate-900">
                    {{ $customerMenu === 'CUST_MY_ORDERS' ? 'Pesanan Aktif Saya' : 'Riwayat Pesanan & Ulasan Layanan' }}
                </h2>

                @if($listOrders->isEmpty())
                    <p class="text-xs text-slate-500 italic py-6 text-center">Belum ada pesanan pada daftar ini.</p>
                @else
                    <div class="space-y-4">
                        @foreach($listOrders as $ord)
                            <div class="p-4 rounded-xl border border-slate-200 space-y-3 text-xs">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="px-2.5 py-0.5 rounded-full font-bold border {{ $orderStatusMap[$ord->status]['badge'] ?? '' }}">
                                            {{ $orderStatusMap[$ord->status]['label'] ?? $ord->status }}
                                        </span>
                                        <span class="px-2.5 py-0.5 rounded-full font-bold border {{ $escrowStatusMap[$ord->escrow_status]['badge'] ?? '' }}">
                                            {{ $escrowStatusMap[$ord->escrow_status]['label'] ?? $ord->escrow_status }}
                                        </span>
                                    </div>
                                    <span class="text-slate-400">Kode: {{ $ord->order_code }} • {{ \App\Helpers\FormatHelper::formatWIB($ord->updated_at) }}</span>
                                </div>

                                @if($ord->agreed_price > 0 && $ord->escrow_status === 'UNPAID' && !in_array($ord->status, ['CANCELLED', 'COMPLETED']))
                                    <div class="p-3.5 rounded-xl bg-amber-50 border-2 border-amber-400 text-amber-950 space-y-1">
                                        <p class="font-extrabold flex items-center gap-1.5 text-rose-800">
                                            <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                                            <span>PERINGATAN: Anda Wajib Membayar Terlebih Dahulu!</span>
                                        </p>
                                        <p>
                                            Pesanan ini <strong>tidak dapat diproses oleh Admin</strong> sebelum Anda menyetorkan dana sebesar <strong>{{ \App\Helpers\FormatHelper::formatIDR($ord->agreed_price) }}</strong> ke Rekening Bersama (Escrow).
                                            <strong>Perhatian: Setelah Anda membayar, pesanan ini TIDAK DAPAT DIBATALKAN.</strong>
                                        </p>
                                    </div>
                                @endif

                                <div>
                                    <h3 class="font-bold text-sm text-slate-900">{{ $ord->title }}</h3>
                                    <p class="text-slate-600 mt-0.5">{{ $ord->description }}</p>
                                    <p class="text-slate-500 mt-1">
                                        Barang/Dokumen: <strong>{{ $ord->items_detail }}</strong> •
                                        Pengambilan: <strong>{{ $ord->pickup_location }}</strong> →
                                        Penyerahan: <strong>{{ $ord->delivery_location }}</strong>
                                    </p>
                                    @if($ord->supporting_file_path)
                                        <div class="mt-2">
                                            <a href="{{ asset('storage/' . $ord->supporting_file_path) }}" target="_blank"
                                               class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-blue-50 border border-blue-200 text-blue-900 font-bold">
                                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                                <span>Lihat File PDF Lampiran Saya</span>
                                            </a>
                                        </div>
                                    @endif
                                </div>

                                <!-- Tampilan Bukti 1 (Pengambilan/Barang Jadi) & Bukti 2 (Penyerahan) yang Dikirim Admin -->
                                @if($ord->receipt_proof_path || $ord->delivery_proof_path)
                                    <div class="p-3.5 rounded-xl bg-teal-50 border border-teal-300 space-y-2">
                                        <p class="font-extrabold text-teal-950">Kiriman Foto Bukti dari Admin ({{ $ord->ownerAdmin?->name }}):</p>
                                        <div class="flex flex-wrap gap-3">
                                            @if($ord->receipt_proof_path)
                                                <a href="{{ asset('storage/' . $ord->receipt_proof_path) }}" target="_blank"
                                                   class="px-3 py-1.5 rounded-lg bg-white border border-teal-400 text-teal-900 font-extrabold flex items-center gap-1.5 shadow-sm">
                                                    <i data-lucide="check-circle" class="w-3.5 h-3.5 text-teal-600"></i>
                                                    <span>Lihat Bukti 1: Barang Sudah Diambil / Selesai Di-print</span>
                                                </a>
                                            @endif
                                            @if($ord->delivery_proof_path)
                                                <a href="{{ asset('storage/' . $ord->delivery_proof_path) }}" target="_blank"
                                                   class="px-3 py-1.5 rounded-lg bg-white border border-emerald-400 text-emerald-900 font-extrabold flex items-center gap-1.5 shadow-sm">
                                                    <i data-lucide="package-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                                    <span>Lihat Bukti 2: Foto Penyerahan Barang ke Customer</span>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 space-y-1">
                                    <p class="font-bold text-[11px] text-slate-700">Riwayat Siklus Hidup Pesanan & Escrow (WIB):</p>
                                    @foreach($ord->histories as $h)
                                        <p class="text-[11px] text-slate-600">
                                            • [{{ \App\Helpers\FormatHelper::formatWIB($h->created_at) }}] <strong>{{ $h->actor_name }}</strong>: {{ $h->note }}
                                        </p>
                                    @endforeach
                                </div>

                                <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100">
                                    <div class="space-y-0.5">
                                        <span class="font-bold text-teal-800 block">
                                            @if($ord->agreed_price !== null)
                                                Harga Disepakati (Deal): {{ \App\Helpers\FormatHelper::formatIDR($ord->agreed_price) }}
                                            @elseif($ord->offered_price)
                                                Nego Harga dari Admin ({{ $ord->ownerAdmin?->name }}): {{ \App\Helpers\FormatHelper::formatIDR($ord->offered_price) }} (Patokan Anda: {{ \App\Helpers\FormatHelper::formatIDR($ord->customer_budget) }})
                                            @else
                                                Patokan Harga Anda Saat Ini: {{ \App\Helpers\FormatHelper::formatIDR($ord->customer_budget) }} (Menunggu Konfirmasi Admin)
                                            @endif
                                        </span>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="button" @click="chatOrderId = {{ $ord->id }}"
                                                class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold flex items-center gap-1.5">
                                            <i data-lucide="message-square" class="w-3.5 h-3.5 text-teal-300"></i>
                                            <span>Chat ({{ $ord->chats->count() }})</span>
                                        </button>

                                        @if($ord->agreed_price > 0 && $ord->escrow_status === 'UNPAID' && !in_array($ord->status, ['CANCELLED', 'COMPLETED']))
                                            <button type="button"
                                                    @click="escrowOrderId = {{ $ord->id }}; escrowOrderTitle = '{{ addslashes($ord->title) }}'; escrowOrderPrice = '{{ \App\Helpers\FormatHelper::formatIDR($ord->agreed_price) }}'"
                                                    class="px-3.5 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-white font-extrabold flex items-center gap-1.5 shadow-sm">
                                                <i data-lucide="credit-card" class="w-3.5 h-3.5"></i>
                                                <span>Bayar Sekarang ({{ \App\Helpers\FormatHelper::formatIDR($ord->agreed_price) }})</span>
                                            </button>
                                        @endif

                                        <!-- 3 PILIHAN SAAT ADMIN MENGAJUKAN NEGO: SETUJUI HARGA, NEGO LAGI, ATAU TOLAK NEGO -->
                                        @if($ord->status === 'WAITING_CUSTOMER_APPROVAL')
                                            <form action="{{ route('customer.orders.respond-offer', $ord) }}" method="POST" class="inline">
                                                @csrf
                                                <input type="hidden" name="decision" value="APPROVE">
                                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-600 text-white font-bold">
                                                    1. Setujui Harga ({{ \App\Helpers\FormatHelper::formatIDR($ord->offered_price) }})
                                                </button>
                                            </form>

                                            <button type="button"
                                                    @click="counterOfferOrderId = (counterOfferOrderId === {{ $ord->id }} ? null : {{ $ord->id }})"
                                                    class="px-3 py-1.5 rounded-lg bg-indigo-700 hover:bg-indigo-600 text-white font-bold">
                                                2. Nego Lagi (Tawar Balik)
                                            </button>

                                            <form action="{{ route('customer.orders.respond-offer', $ord) }}" method="POST" class="inline">
                                                @csrf
                                                <input type="hidden" name="decision" value="REJECT">
                                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white font-bold">
                                                    3. Tolak Nego (Cari Admin Lain)
                                                </button>
                                            </form>
                                        @endif

                                        @if($ord->status === 'WAITING_CUSTOMER_COMPLETION')
                                            <form action="{{ route('customer.orders.confirm-completion', $ord) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('Apakah barang/dokumen sudah Anda terima dengan baik? Menekan OK akan mencairkan dana Escrow ke Saldo Admin.')">
                                                @csrf
                                                <button type="submit" class="px-4 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-600 text-white font-extrabold flex items-center gap-1.5 shadow-sm">
                                                    <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                                                    <span>Pesanan Selesai (Cairkan Dana ke Admin)</span>
                                                </button>
                                            </form>
                                        @endif

                                        @if($ord->escrow_status === 'UNPAID' && in_array($ord->status, ['WAITING_REVIEW', 'WAITING_OFFER', 'APPROVED_FOR_PROCESS']))
                                            <form action="{{ route('customer.orders.cancel', $ord) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini?')">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 rounded-lg border border-rose-300 text-rose-700 font-bold hover:bg-rose-50">
                                                    Batalkan Pesanan
                                                </button>
                                            </form>
                                        @elseif($ord->escrow_status === 'HELD_IN_ESCROW')
                                            <span class="px-2.5 py-1 rounded bg-slate-100 text-slate-600 font-bold text-[11px] flex items-center gap-1">
                                                <i data-lucide="lock" class="w-3 h-3"></i>
                                                <span>Sudah Dibayar (Tidak Dapat Dibatalkan)</span>
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Form Input Nego Lagi (Muncul saat tombol "2. Nego Lagi" diklik) -->
                                @if($ord->status === 'WAITING_CUSTOMER_APPROVAL')
                                    <form x-show="counterOfferOrderId === {{ $ord->id }}" x-cloak
                                          action="{{ route('customer.orders.respond-offer', $ord) }}" method="POST"
                                          class="p-3.5 rounded-xl bg-indigo-50 border border-indigo-300 space-y-2 mt-2">
                                        @csrf
                                        <input type="hidden" name="decision" value="COUNTER">
                                        <p class="font-extrabold text-indigo-950">
                                            Ajukan Nego Harga Balik ke Admin {{ $ord->ownerAdmin?->name }} (Tawaran Admin saat ini: {{ \App\Helpers\FormatHelper::formatIDR($ord->offered_price) }}):
                                        </p>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <input type="number" name="counter_price" required min="1000"
                                                   value="{{ (int) round(($ord->customer_budget + $ord->offered_price) / 2) }}"
                                                   placeholder="Nominal Nego Anda (Rp)"
                                                   class="px-3 py-1.5 rounded-lg border border-indigo-300 bg-white font-extrabold w-44">
                                            <input type="text" name="counter_note" placeholder="Catatan nego balik (mis: naik sedikit ya kak, budget mahasiswa)..."
                                                   class="flex-1 px-3 py-1.5 rounded-lg border border-indigo-300 bg-white">
                                            <button type="submit" class="px-4 py-1.5 rounded-lg bg-indigo-900 text-white font-bold">
                                                Kirim Nego Balik ke Admin
                                            </button>
                                        </div>
                                    </form>
                                @endif

                                @if($ord->status === 'COMPLETED')
                                    <div class="pt-2 border-t border-slate-100">
                                        @if($ord->review)
                                            <div class="p-3 rounded-lg bg-emerald-50 border border-emerald-200">
                                                <p class="font-bold text-emerald-950">Ulasan Anda ({{ $ord->review->rating }}/5 ★):</p>
                                                <p class="text-emerald-900 mt-0.5">"{{ $ord->review->comment }}"</p>
                                            </div>
                                        @else
                                            <form action="{{ route('customer.orders.review', $ord) }}" method="POST" class="flex flex-wrap items-center gap-2">
                                                @csrf
                                                <select name="rating" class="px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white font-bold">
                                                    <option value="5">5 ★ Sangat Puas</option>
                                                    <option value="4">4 ★ Puas</option>
                                                    <option value="3">3 ★ Cukup</option>
                                                    <option value="2">2 ★ Kurang</option>
                                                    <option value="1">1 ★ Buruk</option>
                                                </select>
                                                <input type="text" name="comment" required minlength="5" placeholder="Tulis ulasan jujur Anda (min. 5 karakter)..." class="flex-1 px-3 py-1.5 rounded-lg border border-slate-300">
                                                <button type="submit" class="px-4 py-1.5 rounded-lg bg-blue-900 text-white font-bold">Kirim Ulasan</button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <!-- 5. PENAWARAN HARGA (DILENGKAPI 3 PILIHAN: SETUJUI HARGA, NEGO LAGI, DAN TOLAK NEGO) -->
        @if($customerMenu === 'CUST_PRICE_OFFERS')
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                <h2 class="text-xl font-extrabold text-slate-900">Penawaran Nego Harga dari Admin Layanan</h2>
                <p class="text-xs text-slate-500">
                    Anda memiliki 3 pilihan terhadap penawaran Admin: <strong>Setujui Harga</strong>, <strong>Nego Lagi (Ajukan Harga Balik)</strong>, atau <strong>Tolak Nego (Kembalikan ke Bursa)</strong>.
                </p>
                @if($myPendingOffers->isEmpty())
                    <p class="text-xs text-slate-500 italic py-6 text-center">Tidak ada penawaran harga yang menunggu persetujuan saat ini.</p>
                @else
                    @foreach($myPendingOffers as $ord)
                        <div class="p-5 rounded-xl bg-amber-50/70 border border-amber-300 space-y-3 text-xs">
                            <div class="flex flex-wrap justify-between font-bold text-amber-950 gap-2">
                                <span class="text-sm">{{ $ord->title }} (Patokan Anda: {{ \App\Helpers\FormatHelper::formatIDR($ord->customer_budget) }})</span>
                                <span class="text-base font-extrabold">Tawaran Admin: {{ \App\Helpers\FormatHelper::formatIDR($ord->offered_price) }}</span>
                            </div>
                            <p class="text-slate-700">
                                Admin Penyedia: <strong>{{ $ord->ownerAdmin?->name }}</strong> — Catatan Nego: "{{ $ord->offer_note }}"
                            </p>

                            <div class="flex flex-wrap gap-2">
                                <form action="{{ route('customer.orders.respond-offer', $ord) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="decision" value="APPROVE">
                                    <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-700 hover:bg-emerald-600 text-white font-bold">
                                        1. Setujui Harga ({{ \App\Helpers\FormatHelper::formatIDR($ord->offered_price) }})
                                    </button>
                                </form>

                                <form action="{{ route('customer.orders.respond-offer', $ord) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="decision" value="REJECT">
                                    <button type="submit" class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white font-bold">
                                        3. Tolak Nego & Kembalikan ke Bursa Admin Lain
                                    </button>
                                </form>
                            </div>

                            <!-- Form Langsung untuk Opsi 2: Nego Lagi -->
                            <form action="{{ route('customer.orders.respond-offer', $ord) }}" method="POST"
                                  class="p-3.5 rounded-xl bg-white border border-indigo-300 space-y-2">
                                @csrf
                                <input type="hidden" name="decision" value="COUNTER">
                                <label class="block font-extrabold text-indigo-950">
                                    2. Nego Lagi (Ajukan Harga Tawar Balik Anda ke Admin {{ $ord->ownerAdmin?->name }}):
                                </label>
                                <div class="flex flex-wrap items-center gap-2">
                                    <input type="number" name="counter_price" required min="1000"
                                           value="{{ (int) round(($ord->customer_budget + $ord->offered_price) / 2) }}"
                                           class="px-3 py-1.5 rounded-lg border border-slate-300 font-extrabold w-44">
                                    <input type="text" name="counter_note" placeholder="Alasan tawar balik Anda..."
                                           class="flex-1 px-3 py-1.5 rounded-lg border border-slate-300">
                                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-indigo-800 hover:bg-indigo-700 text-white font-bold">
                                        Kirim Nego Lagi
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endforeach
                @endif
            </div>
        @endif

        <!-- 7. PROFIL AKADEMIK MAHASISWA -->
        @if($customerMenu === 'CUST_PROFILE')
            <form action="{{ route('customer.profile.update') }}" method="POST" class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4 text-xs">
                @csrf
                <h2 class="text-xl font-extrabold text-slate-900">Pengelolaan Profil Akademik Mahasiswa</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Lengkap *</label>
                        <input type="text" name="name" value="{{ $currentUser->name }}" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Universitas *</label>
                        <input type="text" name="university" value="{{ $currentUser->university }}" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">1. Fakultas (UPN "Veteran" Yogyakarta) *</label>
                        <select name="faculty_id" x-model="profFaculty" @change="profProgram = ''; profClass = ''" required class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white font-semibold">
                            <option value="">-- Pilih Fakultas --</option>
                            @foreach($faculties as $f)
                                <option value="{{ $f->id }}">{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">2. Program Studi *</label>
                        <select name="study_program_id" x-model="profProgram" @change="profClass = ''" required class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white font-semibold">
                            <option value="">-- Pilih Program Studi --</option>
                            <template x-for="p in filteredProfPrograms" :key="p.id">
                                <option :value="p.id" :selected="String(p.id) === String(profProgram)" x-text="p.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">3. Kelas *</label>
                        <select name="class_id" x-model="profClass" required class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white font-semibold">
                            <option value="">-- Pilih Kelas --</option>
                            <template x-for="c in filteredProfClasses" :key="c.id">
                                <option :value="c.id" :selected="String(c.id) === String(profClass)" x-text="c.name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="flex items-center pt-5">
                        <label class="flex items-center gap-2 font-bold text-slate-700 cursor-pointer">
                            <input type="checkbox" name="show_class_publicly" value="1" {{ $currentUser->show_class_publicly ? 'checked' : '' }}>
                            <span>Izinkan tampilan Kelas pada identitas akademik publik</span>
                        </label>
                    </div>
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-900 text-white font-bold">
                    Validasi & Simpan Profil Akademik
                </button>
            </form>
        @endif

        <!-- 8. NOTIFIKASI BARU -->
        @if($customerMenu === 'CUST_NOTIFICATIONS')
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4 text-xs">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-xl font-extrabold text-slate-900">Notifikasi Baru Saya</h2>
                        <p class="text-slate-500">Notifikasi yang telah Anda tandai dibaca akan langsung disembunyikan agar tidak mengganggu notifikasi terbaru.</p>
                    </div>
                    @if($notifications->isNotEmpty())
                        <form action="{{ route('notifications.read-all') }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3.5 py-2 rounded-xl bg-teal-700 hover:bg-teal-600 text-white font-bold flex items-center gap-1.5">
                                <i data-lucide="check-check" class="w-4 h-4"></i>
                                <span>Tandai Semua Sudah Dibaca (Bersihkan)</span>
                            </button>
                        </form>
                    @endif
                </div>

                @forelse($notifications as $n)
                    <div class="p-3.5 rounded-xl bg-blue-50/50 border border-blue-200 flex items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 font-bold text-slate-900">
                                <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                                <span>{{ $n->title }}</span>
                                <span class="text-slate-400 font-normal">• {{ \App\Helpers\FormatHelper::formatWIB($n->created_at) }}</span>
                            </div>
                            <p class="text-slate-600 mt-1">{{ $n->message }}</p>
                        </div>
                        <form action="{{ route('notifications.read', $n) }}" method="POST" class="shrink-0">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold">
                                Sudah Dibaca
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="text-slate-500 italic py-6 text-center">Tidak ada notifikasi baru yang belum dibaca.</p>
                @endforelse
            </div>
        @endif

        <!-- 9. PENDAFTARAN SEBAGAI ADMIN -->
        @if($customerMenu === 'CUST_REGISTER_ADMIN')
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-5 text-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="text-xl font-extrabold text-slate-900">Pendaftaran sebagai Admin Penyedia Layanan (Wajib Unggah KTM)</h2>
                        <p class="text-slate-500 mt-0.5">Berkas fisik KTM divalidasi SHA-256 Checksum dan disimpan di ruang privat Laravel Storage.</p>
                    </div>
                    <span class="px-3 py-1 rounded-full font-bold border {{ $verifStatusMap[$currentUser->admin_verification_status]['badge'] ?? '' }}">
                        {{ $verifStatusMap[$currentUser->admin_verification_status]['label'] ?? $currentUser->admin_verification_status }}
                    </span>
                </div>

                @if($myAdminApp && $myAdminApp->status === 'REJECTED')
                    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900">
                        <p class="font-bold">Permohonan Admin Sebelumnya Ditolak</p>
                        <p>Alasan: {{ $myAdminApp->rejection_reason }}</p>
                    </div>
                @endif

                @if($currentUser->admin_verification_status === 'PENDING')
                    <div class="p-5 rounded-xl bg-amber-50 border border-amber-200 text-amber-950 space-y-2">
                        <p class="font-bold text-sm">Permohonan Anda Sedang Menunggu Verifikasi Administrator Utama</p>
                        <p>Berkas KTM: <strong>{{ $myAdminApp?->ktm_file_name }}</strong> (SHA-256: <code>{{ $myAdminApp?->ktm_checksum }}</code>)</p>
                    </div>
                @elseif($currentUser->admin_verification_status === 'VERIFIED')
                    <div class="p-5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-950 flex items-center justify-between">
                        <div>
                            <p class="font-bold text-sm">Akun Anda Telah Diverifikasi sebagai Admin!</p>
                            <p>Anda dapat mengelola layanan jastip dan pesanan di Admin Dashboard.</p>
                        </div>
                        <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 rounded-xl bg-teal-700 text-white font-bold">Buka Admin Dashboard</a>
                    </div>
                @else
                    <form action="{{ route('customer.ktm.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold mb-1">Nama Lengkap Sesuai KTM *</label>
                                <input type="text" name="full_name" value="{{ $currentUser->name }}" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
                            </div>
                            <div>
                                <label class="block font-bold mb-1">Nomor Induk Mahasiswa (NIM - Privat) *</label>
                                <input type="text" name="nim" value="{{ $currentUser->nim }}" required placeholder="Contoh: 123220001" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                            </div>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-50 border-2 border-dashed border-slate-300 space-y-2">
                            <label class="block font-bold text-slate-900">Pilih & Unggah Berkas Kartu Tanda Mahasiswa (JPG / PNG / PDF, Maks {{ $systemConfig->max_ktm_size_mb }}MB) *</label>
                            <input type="file" name="ktm_file" accept=".jpg,.jpeg,.png,.pdf" required class="text-xs block w-full">
                        </div>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-900 text-white font-bold">
                            Unggah KTM & Kirim Permohonan Admin
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </section>

    <!-- MODAL PEMBAYARAN REKENING BERSAMA (ESCROW) -->
    <div x-show="escrowOrderId !== null" x-cloak class="fixed inset-0 z-50 bg-slate-900/65 flex items-center justify-center p-4">
        <form :action="'/customer/orders/' + escrowOrderId + '/pay-escrow'" method="POST"
              class="bg-white border border-slate-200 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl text-xs">
            @csrf
            <div class="flex items-center justify-between border-b pb-3">
                <h3 class="font-extrabold text-base text-slate-900">Setor Dana ke Rekening Bersama (Escrow)</h3>
                <button type="button" @click="escrowOrderId = null"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-3.5 rounded-xl bg-teal-50 border border-teal-200 space-y-1">
                <p class="font-extrabold text-sm text-slate-900" x-text="escrowOrderTitle"></p>
                <p class="text-base font-extrabold text-teal-800" x-text="'Total Setoran Escrow: ' + escrowOrderPrice"></p>
            </div>
            <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 space-y-1">
                <p class="font-extrabold">PERINGATAN SEBELUM MEMBAYAR:</p>
                <p>1. Anda wajib membayar terlebih dahulu agar pesanan dapat diproses oleh Admin.</p>
                <p>2. <strong>Setelah Anda menekan tombol Bayar Sekarang, pesanan terkunci dan TIDAK DAPAT DIBATALKAN.</strong></p>
            </div>
            <div class="space-y-2">
                <label class="block font-bold">Pilih Metode Pembayaran (Simulasi):</label>
                <select name="payment_method" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white font-bold">
                    <option value="QRIS Kampus (Bebas Biaya Admin)">QRIS Kampus (Bebas Biaya Admin)</option>
                    <option value="Virtual Account BNI / BRI UPNYK">Virtual Account BNI / BRI UPNYK</option>
                    <option value="Virtual Account BCA / Mandiri">Virtual Account BCA / Mandiri</option>
                    <option value="GoPay / Dana / OVO Mahasiswa">GoPay / Dana / OVO Mahasiswa</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="escrowOrderId = null" class="px-4 py-2 rounded-xl border border-slate-300 font-bold">Nanti Saja</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-teal-700 text-white font-extrabold">Setuju & Bayar Sekarang</button>
            </div>
        </form>
    </div>

    <!-- MODAL RUANG CHAT PESANAN IN-APP -->
    @foreach($myOrders as $ord)
        <div x-show="chatOrderId === {{ $ord->id }}" x-cloak class="fixed inset-0 z-50 bg-slate-900/65 flex items-center justify-center p-4">
            <div class="bg-white border border-slate-200 rounded-2xl max-w-lg w-full flex flex-col max-h-[82vh] shadow-2xl text-xs">
                <div class="p-4 border-b bg-slate-900 text-white rounded-t-2xl flex items-center justify-between">
                    <div>
                        <span class="text-[10px] uppercase text-teal-300 font-bold">Ruang Diskusi In-App ({{ $ord->order_code }})</span>
                        <h3 class="font-extrabold text-sm">{{ $ord->title }}</h3>
                    </div>
                    <button type="button" @click="chatOrderId = null"><i data-lucide="x" class="w-5 h-5"></i></button>
                </div>
                <div class="p-4 flex-1 overflow-y-auto space-y-3 bg-slate-50 min-h-[240px]">
                    @forelse($ord->chats as $m)
                        @php $isMine = $m->sender_id === $currentUser->id; @endphp
                        <div class="flex flex-col {{ $isMine ? 'items-end' : 'items-start' }}">
                            <div class="max-w-[80%] p-3 rounded-2xl space-y-1 {{ $isMine ? 'bg-blue-900 text-white rounded-br-none' : 'bg-white border border-slate-200 text-slate-900 rounded-bl-none' }}">
                                <div class="text-[10px] opacity-80 font-bold">{{ $m->sender_name }} • {{ $m->sender_role }}</div>
                                <p>{{ $m->message }}</p>
                                <p class="text-[9px] opacity-70 text-right">{{ \App\Helpers\FormatHelper::formatWIB($m->created_at) }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-center py-10 text-slate-400">Belum ada pesan diskusi pada pesanan ini.</p>
                    @endforelse
                </div>
                <form action="{{ route('orders.chat.send', $ord) }}" method="POST" class="p-3 border-t bg-white rounded-b-2xl flex gap-2">
                    @csrf
                    <input type="hidden" name="sender_role" value="CUSTOMER">
                    <input type="text" name="message" required placeholder="Ketik pesan ke Admin..." class="flex-1 px-3 py-2 rounded-xl border border-slate-300">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-teal-700 text-white font-bold">Kirim</button>
                </form>
            </div>
        </div>
    @endforeach

    <!-- MODAL LAPORKAN MASALAH -->
    <div x-show="reportTarget !== null" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 flex items-center justify-center p-4">
        <form action="{{ route('customer.report.store') }}" method="POST" class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl text-xs">
            @csrf
            <h3 class="font-extrabold text-base text-slate-900">Laporkan Masalah ke Administrator Utama</h3>
            <input type="hidden" name="target_title" :value="reportTarget">
            <p class="text-slate-600">Objek Laporan: <strong x-text="reportTarget"></strong></p>
            <textarea name="reason" rows="3" required minlength="5" placeholder="Jelaskan kendala atau pelanggaran secara jelas..." class="w-full px-3 py-2 rounded-lg border border-slate-300"></textarea>
            <div class="flex justify-end gap-2">
                <button type="button" @click="reportTarget = null" class="px-4 py-2 rounded-lg border border-slate-300 font-bold">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-rose-700 text-white font-bold">Kirim Laporan</button>
            </div>
        </form>
    </div>
</div>
@endsection