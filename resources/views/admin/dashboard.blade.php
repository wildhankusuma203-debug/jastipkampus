@extends('layouts.app')

@section('content')
@php
    $orderStatusMap = \App\Helpers\FormatHelper::orderStatusMap();
    $escrowStatusMap = \App\Helpers\FormatHelper::escrowStatusMap();

    $adminNavItems = [
        ['id' => 'ADM_HOME', 'label' => 'Dashboard & Dompet Escrow', 'icon' => 'layout-dashboard', 'badge' => 0],
        ['id' => 'ADM_SERVICES', 'label' => 'Kelola Layanan Saya', 'icon' => 'briefcase', 'badge' => $myServices->count()],
        ['id' => 'ADM_CATEGORIES', 'label' => 'Kelola Kategori Dinamis', 'icon' => 'layers', 'badge' => $categories->count()],
        ['id' => 'ADM_MARKET_REQUESTS', 'label' => 'Bursa Permintaan Jastip', 'icon' => 'flame', 'badge' => $marketRequests->count()],
        ['id' => 'ADM_INCOMING_REQUESTS', 'label' => 'Pesanan, Chat & Bukti Foto', 'icon' => 'package', 'badge' => $dealOrders->whereNotIn('status', ['COMPLETED', 'CANCELLED'])->count()],
        ['id' => 'ADM_NOTIFICATIONS', 'label' => 'Notifikasi Baru', 'icon' => 'bell', 'badge' => $notifications->count()],
    ];
@endphp

<div x-data="{
        chatOrderId: {{ session('open_chat_order_id') ? (int) session('open_chat_order_id') : 'null' }},
        catName: '',
        catDesc: ''
     }"
     class="grid grid-cols-1 lg:grid-cols-12 gap-6">

    <!-- SIDEBAR ADMIN -->
    <aside class="lg:col-span-3 space-y-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-4 space-y-3 shadow-sm">
            <div class="p-3 rounded-xl bg-teal-900 text-white space-y-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-teal-200">Admin Penyedia Layanan</span>
                <p class="font-extrabold text-sm truncate">{{ $currentUser->name }}</p>
                <p class="text-[11px] text-teal-100 truncate">{{ $currentUser->faculty?->name }} • {{ $currentUser->studyProgram?->name }}</p>
                <p class="text-[11px] text-amber-300 font-extrabold pt-1">
                    Saldo Escrow: {{ \App\Helpers\FormatHelper::formatIDR($currentUser->wallet_balance) }}
                </p>
            </div>

            <nav class="space-y-1">
                @foreach($adminNavItems as $item)
                    @php $isActive = $adminMenu === $item['id']; @endphp
                    <a href="{{ route('admin.dashboard', ['menu' => $item['id']]) }}"
                       class="w-full px-3 py-2.5 rounded-xl text-xs font-bold flex items-center justify-between transition {{ $isActive ? 'bg-teal-700 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span class="flex items-center gap-2.5">
                            <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 shrink-0"></i>
                            <span>{{ $item['label'] }}</span>
                        </span>
                        @if($item['badge'] > 0)
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $isActive ? 'bg-white text-teal-900' : 'bg-teal-100 text-teal-900' }}">
                                {{ $item['badge'] }}
                            </span>
                        @endif
                    </a>
                @endforeach

                <a href="{{ route('customer.dashboard') }}"
                   class="w-full mt-2 px-3 py-2.5 rounded-xl text-xs font-bold bg-blue-50 text-blue-900 border border-blue-200 hover:bg-blue-100 flex items-center gap-2">
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    <span>Kembali ke Dashboard Customer</span>
                </a>
            </nav>
        </div>
    </aside>

    <!-- KONTEN ADMIN -->
    <section class="lg:col-span-9 space-y-6">

        <!-- 1. DASHBOARD & DOMPET ESCROW ADMIN -->
        @if($adminMenu === 'ADM_HOME')
            <div class="space-y-6">
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-5">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-teal-700">Admin Dashboard Terverifikasi</span>
                        <h1 class="text-2xl font-extrabold text-slate-900 mt-0.5">Ringkasan Layanan & Dompet Escrow — {{ $currentUser->name }}</h1>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200">
                            <span class="text-emerald-900 font-semibold">Saldo Dompet Escrow</span>
                            <p class="text-2xl font-extrabold text-emerald-950 mt-1">{{ \App\Helpers\FormatHelper::formatIDR($currentUser->wallet_balance) }}</p>
                        </div>
                        <div class="p-4 rounded-xl bg-teal-50 border border-teal-200">
                            <span class="text-teal-900 font-semibold">Layanan Aktif Saya</span>
                            <p class="text-2xl font-extrabold text-teal-950 mt-1">{{ $myServices->where('is_active', true)->count() }}</p>
                        </div>
                        <div class="p-4 rounded-xl bg-indigo-50 border border-indigo-200">
                            <span class="text-indigo-900 font-semibold">Permintaan di Bursa (Belum Deal)</span>
                            <p class="text-2xl font-extrabold text-indigo-950 mt-1">{{ $marketRequests->count() }}</p>
                        </div>
                        <div class="p-4 rounded-xl bg-cyan-50 border border-cyan-200">
                            <span class="text-cyan-900 font-semibold">Pesanan Deal & Diproses</span>
                            <p class="text-2xl font-extrabold text-cyan-950 mt-1">{{ $dealOrders->whereNotIn('status', ['COMPLETED', 'CANCELLED'])->count() }}</p>
                        </div>
                        <div class="p-4 rounded-xl bg-blue-50 border border-blue-200">
                            <span class="text-blue-900 font-semibold">Pesanan Selesai</span>
                            <p class="text-2xl font-extrabold text-blue-950 mt-1">{{ $myManagedOrders->where('status', 'COMPLETED')->count() }}</p>
                        </div>
                        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200">
                            <span class="text-amber-900 font-semibold">Dana Tertahan di Escrow</span>
                            <p class="text-lg font-extrabold text-amber-950 mt-1">
                                {{ \App\Helpers\FormatHelper::formatIDR($myManagedOrders->where('escrow_status', 'HELD_IN_ESCROW')->sum('agreed_price')) }}
                            </p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('admin.wallet.withdraw') }}" method="POST" class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4 text-xs">
                    @csrf
                    <h2 class="text-base font-extrabold text-slate-900">Pencairan Saldo Dompet Escrow ke Rekening / E-Wallet Mahasiswa (Simulasi)</h2>
                    <p class="text-slate-500">Dana dari pesanan yang telah dikonfirmasi Selesai oleh Customer otomatis masuk ke Saldo Dompet Anda.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <input type="text" name="bank_name" value="BNI / BCA / GoPay / Dana" required placeholder="Nama Bank / E-Wallet" class="px-3 py-2 rounded-lg border border-slate-300">
                        <input type="text" name="account_number" required placeholder="Nomor Rekening / No. HP E-Wallet" class="px-3 py-2 rounded-lg border border-slate-300">
                        <button type="submit" class="px-4 py-2 rounded-lg bg-teal-700 hover:bg-teal-600 text-white font-bold">
                            Cairkan Saldo ({{ \App\Helpers\FormatHelper::formatIDR($currentUser->wallet_balance) }})
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- 2. KELOLA LAYANAN SAYA -->
        @if($adminMenu === 'ADM_SERVICES')
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <form action="{{ route('admin.services.save') }}" method="POST" enctype="multipart/form-data" class="lg:col-span-5 bg-white border border-slate-200 rounded-2xl p-5 space-y-3 text-xs">
                    @csrf
                    <input type="hidden" name="service_id" value="{{ $serviceToEdit?->id }}">
                    <h3 class="font-extrabold text-base text-slate-900">
                        {{ $serviceToEdit ? 'Edit Layanan Milik Saya' : 'Buat Layanan Jastip Baru' }}
                    </h3>
                    @if($categories->isEmpty())
                        <div class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-900">
                            Buat kategori terlebih dahulu di menu <a href="{{ route('admin.dashboard', ['menu' => 'ADM_CATEGORIES']) }}" class="underline font-bold">Kelola Kategori Dinamis</a>.
                        </div>
                    @endif
                    <div>
                        <label class="block font-bold mb-1">Nama Layanan *</label>
                        <input type="text" name="name" required value="{{ old('name', $serviceToEdit?->name) }}" placeholder="Contoh: Jasa Print & Jilid Skripsi / Ambil KTM" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                    </div>
                    <div>
                        <label class="block font-bold mb-1">Kategori Dinamis *</label>
                        <select name="category_id" required class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}" {{ old('category_id', $serviceToEdit?->category_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold mb-1">Deskripsi Layanan *</label>
                        <textarea name="description" rows="3" required class="w-full px-3 py-2 rounded-lg border border-slate-300">{{ old('description', $serviceToEdit?->description) }}</textarea>
                    </div>
                    <div>
                        <label class="block font-bold mb-1">Harga Layanan (IDR) *</label>
                        <input type="number" name="price" required min="1000" value="{{ old('price', $serviceToEdit?->price ?? 15000) }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-bold">
                    </div>
                    <div>
                        <label class="block font-bold mb-1">Keterangan Persyaratan Layanan</label>
                        <input type="text" name="requirements" value="{{ old('requirements', $serviceToEdit?->requirements) }}" placeholder="Contoh: Wajib melampirkan file PDF siap cetak / surat kuasa" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                    </div>
                    <div class="p-3 rounded-xl bg-teal-50/70 border border-teal-200 space-y-1.5">
                        <label class="block font-extrabold text-teal-950">
                            Unggah File Contoh Syarat / Template Surat Kuasa (PDF, Maks 5MB)
                        </label>
                        <input type="file" name="requirement_file" accept="application/pdf,.pdf" class="block w-full text-[11px]">
                    </div>
                    <label class="flex items-center gap-2 font-bold pt-1">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $serviceToEdit ? $serviceToEdit->is_active : true) ? 'checked' : '' }}>
                        <span>Publikasikan sebagai Layanan Aktif</span>
                    </label>
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-teal-700 text-white font-bold">Simpan Layanan</button>
                </form>

                <div class="lg:col-span-7 bg-white border border-slate-200 rounded-2xl p-5 space-y-3 text-xs">
                    <h3 class="font-extrabold text-base text-slate-900">Daftar Layanan Milik Saya ({{ $myServices->count() }})</h3>
                    @forelse($myServices as $s)
                        <div class="p-4 rounded-xl border border-slate-200 flex items-center justify-between gap-3">
                            <div class="space-y-1">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $s->is_active ? 'bg-emerald-100 text-emerald-900' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $s->is_active ? 'AKTIF & DIPUBLIKASIKAN' : 'NONAKTIF' }}
                                </span>
                                <p class="font-bold text-sm text-slate-900">{{ $s->name }}</p>
                                <p class="font-extrabold text-teal-700">{{ \App\Helpers\FormatHelper::formatIDR($s->price) }}</p>
                                @if($s->requirement_file_path)
                                    <a href="{{ asset('storage/' . $s->requirement_file_path) }}" target="_blank" class="inline-flex items-center gap-1 text-blue-800 font-bold underline text-[11px]">
                                        <i data-lucide="file-text" class="w-3 h-3"></i>
                                        <span>Lihat File PDF Syarat Layanan</span>
                                    </a>
                                @endif
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('admin.dashboard', ['menu' => 'ADM_SERVICES', 'edit_service_id' => $s->id]) }}"
                                   class="px-3 py-1.5 rounded border border-slate-300 font-bold">Edit</a>
                                <form action="{{ route('admin.services.toggle', $s) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded bg-slate-900 text-white font-bold">
                                        {{ $s->is_active ? 'Nonaktifkan' : 'Publikasikan' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-slate-500 italic py-6 text-center">Anda belum memiliki layanan yang dibuat.</p>
                    @endforelse
                </div>
            </div>
        @endif

        <!-- 3. KELOLA KATEGORI DINAMIS (DILENGKAPI CONTOH CETAK & JILID TUGAS / SKRIPSI & TOMBOL HAPUS) -->
        @if($adminMenu === 'ADM_CATEGORIES')
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 text-xs">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="font-extrabold text-base text-slate-900">Manajemen Kategori Layanan Dinamis</h3>
                        <p class="text-slate-500">Tambahkan kategori layanan baru atau klik tombol isi contoh otomatis di samping.</p>
                    </div>
                    <button type="button"
                            @click="catName = 'Cetak & Jilid Tugas / Skripsi'; catDesc = 'Menitipkan file (PDF) untuk dicetakkan di fotokopi sekitar kampus dan diantarkan langsung ke customer'"
                            class="px-3 py-1.5 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-900 border border-teal-300 font-bold">
                        Isi Contoh: Cetak & Jilid Tugas / Skripsi
                    </button>
                </div>

                <form action="{{ route('admin.categories.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @csrf
                    <input type="text" name="name" x-model="catName" required
                           placeholder="Contoh: Cetak & Jilid Tugas / Skripsi"
                           class="px-3 py-2 rounded-lg border border-slate-300">
                    <input type="text" name="description" x-model="catDesc"
                           placeholder="Contoh: Menitipkan file (PDF) untuk dicetakkan di fotokopi sekitar kampus dan diantarkan langsung ke customer"
                           class="px-3 py-2 rounded-lg border border-slate-300">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-teal-700 text-white font-bold">+ Tambah Kategori ke Database</button>
                </form>

                <div class="space-y-2 pt-2">
                    @forelse($categories as $c)
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-4">
                            <div>
                                <span class="font-bold text-slate-900">{{ $c->name }}</span>
                                <p class="text-slate-600 mt-0.5">{{ $c->description }}</p>
                                <span class="inline-block mt-1 px-2 py-0.5 rounded bg-slate-200 text-slate-700 text-[10px] font-bold">
                                    {{ $c->services_count }} Layanan Menggunakan Kategori Ini
                                </span>
                            </div>
                            <form action="{{ route('admin.categories.destroy', $c) }}" method="POST"
                                  onsubmit="return confirm('Hapus kategori {{ addslashes($c->name) }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold flex items-center gap-1 shrink-0">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    <span>Hapus</span>
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="text-slate-500 italic">Belum ada kategori di database.</p>
                    @endforelse
                </div>
            </div>
        @endif

        <!-- 4A. MENU BURSA PERMINTAAN JASTIP (MENAMPILKAN PERMINTAAN BARU & NEGO BALIK DARI CUSTOMER) -->
        @if($adminMenu === 'ADM_MARKET_REQUESTS')
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 text-xs">
                <div class="border-b border-slate-100 pb-3">
                    <span class="px-2.5 py-0.5 rounded bg-indigo-100 text-indigo-900 text-[10px] font-extrabold uppercase">Bursa Permintaan Terbuka</span>
                    <h3 class="font-extrabold text-lg text-slate-900 mt-1">Bursa Permintaan Jastip Mahasiswa (Menunggu Deal Harga)</h3>
                    <p class="text-slate-500">
                        Menampilkan permintaan jastip bebas dari Customer atau pengajuan <strong>Nego Lagi (Nego Balik)</strong> dari Customer. Setelah mencapai <strong>Deal Harga</strong>, pesanan otomatis pindah ke menu <strong>Pesanan, Chat & Bukti Foto</strong>.
                    </p>
                </div>

                @forelse($marketRequests as $ord)
                    <div class="p-4 rounded-xl border border-indigo-200 bg-indigo-50/30 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="px-2.5 py-0.5 rounded-full font-bold border {{ $orderStatusMap[$ord->status]['badge'] ?? '' }}">
                                {{ $orderStatusMap[$ord->status]['label'] ?? $ord->status }}
                            </span>
                            <span class="font-semibold text-teal-900 bg-white px-2.5 py-0.5 rounded border border-teal-200">
                                Pemohon: {{ $ord->customer?->name }} ({{ $ord->customerFaculty?->name }} • {{ $ord->customerProgram?->name }} • {{ $ord->customerClass?->name }})
                            </span>
                        </div>

                        <div>
                            <h4 class="font-extrabold text-sm text-slate-900">{{ $ord->title }}</h4>
                            <p class="text-slate-600 mt-0.5">{{ $ord->description }}</p>
                            <p class="text-slate-500 mt-1">
                                Barang/Dokumen: <strong>{{ $ord->items_detail }}</strong> •
                                Pengambilan: <strong>{{ $ord->pickup_location }}</strong> →
                                Penyerahan: <strong>{{ $ord->delivery_location }}</strong> •
                                Tenggat: <strong>{{ \App\Helpers\FormatHelper::formatWIB($ord->deadline) }}</strong>
                            </p>
                            @if($ord->offer_note)
                                <p class="mt-1 text-indigo-900 font-bold bg-indigo-100/80 px-2.5 py-1 rounded inline-block">
                                    Info Nego Terakhir: {{ $ord->offer_note }}
                                </p>
                            @endif
                            @if($ord->supporting_file_path)
                                <div class="mt-2">
                                    <a href="{{ asset('storage/' . $ord->supporting_file_path) }}" target="_blank"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-900 text-white font-bold">
                                        <i data-lucide="file-down" class="w-3.5 h-3.5"></i>
                                        <span>Unduh / Lihat File PDF dari Customer (Surat Syarat / File Print)</span>
                                    </a>
                                </div>
                            @endif
                        </div>

                        @if($ord->status === 'WAITING_OFFER')
                            <div class="p-3.5 rounded-xl bg-white border border-indigo-200 space-y-3">
                                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                                    <div>
                                        <span class="text-[11px] text-indigo-800 font-semibold">Harga Patokan / Nego Balik dari Customer:</span>
                                        <p class="text-base font-extrabold text-indigo-950">{{ \App\Helpers\FormatHelper::formatIDR($ord->customer_budget) }}</p>
                                    </div>
                                    @if($ord->customer_budget > 0)
                                        <form action="{{ route('admin.orders.accept-budget', $ord) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-3.5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-600 text-white font-extrabold flex items-center gap-1.5 shadow-sm">
                                                <i data-lucide="check" class="w-4 h-4"></i>
                                                <span>Deal! Setujui Harga Customer ({{ \App\Helpers\FormatHelper::formatIDR($ord->customer_budget) }})</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <form action="{{ route('admin.orders.send-offer', $ord) }}" method="POST" class="space-y-1.5">
                                    @csrf
                                    <p class="font-bold text-indigo-950">Masih terlalu rendah? Ajukan Nego Harga ke Customer:</p>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <input type="number" name="offered_price" required min="1000" value="{{ $ord->customer_budget ?: 15000 }}" class="px-3 py-1.5 rounded border border-slate-300 bg-white font-bold w-40">
                                        <input type="text" name="offer_note" placeholder="Alasan nego (mis: biaya cetak warna + parkir)..." class="flex-1 px-3 py-1.5 rounded border border-slate-300 bg-white">
                                        <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-indigo-900 text-white font-bold">Kirim Nego Harga</button>
                                    </div>
                                </form>
                            </div>
                        @elseif($ord->status === 'WAITING_CUSTOMER_APPROVAL')
                            <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-between">
                                <span>Menunggu respon Customer (Setuju / Nego Lagi / Tolak) atas tawaran Anda sebesar <strong>{{ \App\Helpers\FormatHelper::formatIDR($ord->offered_price) }}</strong>.</span>
                                <span class="font-bold text-amber-900">Menunggu Respon Customer</span>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-slate-500 italic py-8 text-center">Belum ada permintaan jastip bebas di bursa saat ini.</p>
                @endforelse
            </div>
        @endif

        <!-- 4B. MENU PESANAN, CHAT & BUKTI FOTO (WAJIB BUKTI 1 DULU BARU BUKTI 2) -->
        @if($adminMenu === 'ADM_INCOMING_REQUESTS')
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 text-xs">
                <div class="border-b border-slate-100 pb-3">
                    <span class="px-2.5 py-0.5 rounded bg-teal-100 text-teal-900 text-[10px] font-extrabold uppercase">Pesanan Deal & Aktif</span>
                    <h3 class="font-extrabold text-lg text-slate-900 mt-1">Pesanan Deal, Diskusi Chat, & Upload Bukti Berurutan</h3>
                    <p class="text-slate-500">
                        Alur Wajib Admin: <strong>1. Mulai Proses Pesanan</strong> (setelah Customer bayar Escrow) → <strong>2. Upload & Kirim Bukti 1 (Pengambilan/Barang Jadi) ke Customer</strong> → <strong>3. Upload Bukti 2 (Penyerahan)</strong> → <strong>4. Konfirmasi Barang Diserahkan</strong>.
                    </p>
                </div>

                @forelse($dealOrders as $ord)
                    <div class="p-4 rounded-xl border border-slate-200 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full font-bold border {{ $orderStatusMap[$ord->status]['badge'] ?? '' }}">
                                    {{ $orderStatusMap[$ord->status]['label'] ?? $ord->status }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full font-bold border {{ $escrowStatusMap[$ord->escrow_status]['badge'] ?? '' }}">
                                    {{ $escrowStatusMap[$ord->escrow_status]['label'] ?? $ord->escrow_status }}
                                </span>
                            </div>
                            <span class="font-semibold text-teal-900 bg-teal-50 px-2.5 py-0.5 rounded border border-teal-200">
                                Customer: {{ $ord->customer?->name }} ({{ $ord->customerFaculty?->name }} • {{ $ord->customerProgram?->name }})
                            </span>
                        </div>

                        <div>
                            <h4 class="font-extrabold text-sm text-slate-900">{{ $ord->title }}</h4>
                            <p class="text-slate-600 mt-0.5">{{ $ord->description }}</p>
                            <p class="text-slate-500 mt-1">
                                Barang/Dokumen: <strong>{{ $ord->items_detail }}</strong> •
                                Pengambilan: <strong>{{ $ord->pickup_location }}</strong> →
                                Penyerahan: <strong>{{ $ord->delivery_location }}</strong>
                            </p>
                            @if($ord->supporting_file_path)
                                <div class="mt-2">
                                    <a href="{{ asset('storage/' . $ord->supporting_file_path) }}" target="_blank"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-900 text-white font-bold">
                                        <i data-lucide="file-down" class="w-3.5 h-3.5"></i>
                                        <span>Unduh File PDF dari Customer (Surat Syarat / Dokumen Print)</span>
                                    </a>
                                </div>
                            @endif
                        </div>

                        <!-- PANEL UPLOAD BUKTI BERURUTAN (BUKTI 1 WAJIB DIKIRIM SEBELUM BUKTI 2 TERBUKA) -->
                        @if($ord->escrow_status === 'HELD_IN_ESCROW' || $ord->status === 'COMPLETED')
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-2.5">
                                <p class="font-extrabold text-slate-900">Tahapan Wajib Pengiriman Foto Bukti ke Customer:</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <!-- BUKTI 1: PENGAMBILAN BARANG / HASIL PRINT SUDAH JADI -->
                                    <form action="{{ route('admin.orders.upload-proof', $ord) }}" method="POST" enctype="multipart/form-data"
                                          class="p-3 rounded-xl bg-white border-2 {{ $ord->receipt_proof_path ? 'border-emerald-400' : 'border-teal-400' }} space-y-2">
                                        @csrf
                                        <input type="hidden" name="proof_type" value="RECEIPT">
                                        <div class="flex items-center justify-between">
                                            <span class="font-extrabold text-slate-900">
                                                Tahap 1: Bukti Pengambilan / Barang Sudah Jadi *
                                            </span>
                                            @if($ord->receipt_proof_path)
                                                <a href="{{ asset('storage/' . $ord->receipt_proof_path) }}" target="_blank" class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-900 font-bold underline">
                                                    Sudah Terkirim (Lihat)
                                                </a>
                                            @else
                                                <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-900 font-bold text-[10px]">Wajib Dikirim Dulu</span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] text-slate-500">
                                            Lampirkan foto bukti bahwa KTM sudah diambil atau dokumen sudah selesai di-print. Foto ini langsung dikirim ke Customer.
                                        </p>
                                        @if($ord->status !== 'COMPLETED')
                                            <div class="flex items-center gap-2 pt-1">
                                                <input type="file" name="proof_file" accept="image/jpeg,image/png" required class="text-[11px] flex-1">
                                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-teal-700 hover:bg-teal-600 text-white font-bold">
                                                    {{ $ord->receipt_proof_path ? 'Ganti & Kirim' : 'Kirim ke Customer' }}
                                                </button>
                                            </div>
                                        @endif
                                    </form>

                                    <!-- BUKTI 2: FOTO PENYERAHAN BARANG (TERKUNCI JIKA BUKTI 1 BELUM DIKIRIM) -->
                                    <form action="{{ route('admin.orders.upload-proof', $ord) }}" method="POST" enctype="multipart/form-data"
                                          class="p-3 rounded-xl border-2 {{ !$ord->receipt_proof_path ? 'bg-slate-100 border-slate-300 opacity-75' : ($ord->delivery_proof_path ? 'bg-white border-emerald-400' : 'bg-white border-purple-400') }} space-y-2">
                                        @csrf
                                        <input type="hidden" name="proof_type" value="DELIVERY">
                                        <div class="flex items-center justify-between">
                                            <span class="font-extrabold text-slate-900">
                                                Tahap 2: Foto Bukti Penyerahan ke Customer *
                                            </span>
                                            @if($ord->delivery_proof_path)
                                                <a href="{{ asset('storage/' . $ord->delivery_proof_path) }}" target="_blank" class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-900 font-bold underline">
                                                    Sudah Terkirim (Lihat)
                                                </a>
                                            @elseif(!$ord->receipt_proof_path)
                                                <span class="px-2 py-0.5 rounded bg-slate-200 text-slate-700 font-bold text-[10px]">Terkunci (Selesaikan Tahap 1)</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded bg-purple-100 text-purple-900 font-bold text-[10px]">Siap Diunggah</span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] text-slate-500">
                                            Baru dapat diisi setelah Anda mengirimkan Bukti Tahap 1 kepada Customer.
                                        </p>
                                        @if($ord->status !== 'COMPLETED')
                                            @if($ord->receipt_proof_path)
                                                <div class="flex items-center gap-2 pt-1">
                                                    <input type="file" name="proof_file" accept="image/jpeg,image/png" required class="text-[11px] flex-1">
                                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-600 text-white font-bold">
                                                        Kirim Bukti Penyerahan
                                                    </button>
                                                </div>
                                            @else
                                                <p class="text-[11px] font-bold text-rose-700 pt-1">
                                                    ⚠ Unggah & kirim Bukti Tahap 1 di sebelah kiri terlebih dahulu!
                                                </p>
                                            @endif
                                        @endif
                                    </form>
                                </div>
                            </div>
                        @else
                            <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-950 font-semibold">
                                Menunggu Customer menyetorkan pembayaran ke Rekening Bersama (Escrow) sebelum pesanan dapat diproses.
                            </div>
                        @endif

                        <!-- Baris Aksi Chat & Pembaruan Status -->
                        <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100">
                            <span class="font-extrabold text-teal-800">
                                Harga Deal (Terkunci): {{ \App\Helpers\FormatHelper::formatIDR($ord->agreed_price) }}
                            </span>

                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" @click="chatOrderId = {{ $ord->id }}" class="px-3 py-1.5 rounded-lg bg-slate-900 text-white font-bold flex items-center gap-1.5">
                                    <i data-lucide="message-square" class="w-3.5 h-3.5 text-teal-300"></i>
                                    <span>Chat Customer ({{ $ord->chats->count() }})</span>
                                </button>

                                @if(in_array($ord->status, ['WAITING_REVIEW', 'APPROVED_FOR_PROCESS']) && $ord->escrow_status === 'HELD_IN_ESCROW')
                                    <form action="{{ route('admin.orders.update-status', $ord) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="next_status" value="IN_PROGRESS">
                                        <input type="hidden" name="note" value="Admin mulai memproses pengambilan/pencetakan pesanan">
                                        <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-cyan-700 hover:bg-cyan-600 text-white font-bold">
                                            1. Mulai Proses Pesanan
                                        </button>
                                    </form>
                                @endif

                                @if($ord->status === 'IN_PROGRESS')
                                    <form action="{{ route('admin.orders.update-status', $ord) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="next_status" value="WAITING_CUSTOMER_COMPLETION">
                                        <input type="hidden" name="note" value="Admin telah menyerahkan barang/dokumen beserta Bukti 1 & Bukti 2. Menunggu konfirmasi selesai dari Customer.">
                                        <button type="submit"
                                                class="px-3.5 py-1.5 rounded-lg font-bold {{ ($ord->receipt_proof_path && $ord->delivery_proof_path) ? 'bg-purple-700 hover:bg-purple-600 text-white' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}">
                                            Konfirmasi Barang Diserahkan (Minta Customer Selesaikan)
                                        </button>
                                    </form>
                                @endif

                                @if($ord->status === 'WAITING_CUSTOMER_COMPLETION')
                                    <span class="px-3 py-1.5 rounded-lg bg-purple-50 text-purple-900 border border-purple-200 font-bold">
                                        Menunggu Customer Menekan "Pesanan Selesai"
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-slate-500 italic py-8 text-center">
                        Belum ada pesanan yang mencapai kesepakatan harga (Deal). Cek menu <strong>Bursa Permintaan Jastip</strong> untuk mengambil permintaan baru.
                    </p>
                @endforelse
            </div>
        @endif

        <!-- 5. NOTIFIKASI ADMIN BARU -->
        @if($adminMenu === 'ADM_NOTIFICATIONS')
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 text-xs">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="font-extrabold text-base text-slate-900">Notifikasi Baru Admin</h3>
                        <p class="text-slate-500">Notifikasi yang sudah Anda baca akan langsung disembunyikan agar tidak mengganggu notifikasi terbaru.</p>
                    </div>
                    @if($notifications->isNotEmpty())
                        <form action="{{ route('notifications.read-all') }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3.5 py-2 rounded-xl bg-teal-700 hover:bg-teal-600 text-white font-bold">
                                Tandai Semua Sudah Dibaca (Bersihkan)
                            </button>
                        </form>
                    @endif
                </div>

                @forelse($notifications as $n)
                    <div class="p-3.5 rounded-xl bg-teal-50/50 border border-teal-200 flex items-center justify-between gap-4">
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
    </section>

    <!-- MODAL CHAT IN-APP UNTUK ADMIN -->
    @foreach($dealOrders as $ord)
        <div x-show="chatOrderId === {{ $ord->id }}" x-cloak class="fixed inset-0 z-50 bg-slate-900/65 flex items-center justify-center p-4">
            <div class="bg-white border border-slate-200 rounded-2xl max-w-lg w-full flex flex-col max-h-[82vh] shadow-2xl text-xs">
                <div class="p-4 border-b bg-slate-900 text-white rounded-t-2xl flex items-center justify-between">
                    <div>
                        <span class="text-[10px] uppercase text-teal-300 font-bold">Diskusi dengan Customer ({{ $ord->customer?->name }})</span>
                        <h3 class="font-extrabold text-sm">{{ $ord->title }}</h3>
                    </div>
                    <button type="button" @click="chatOrderId = null"><i data-lucide="x" class="w-5 h-5"></i></button>
                </div>
                <div class="p-4 flex-1 overflow-y-auto space-y-3 bg-slate-50 min-h-[240px]">
                    @forelse($ord->chats as $m)
                        @php $isMine = $m->sender_id === $currentUser->id; @endphp
                        <div class="flex flex-col {{ $isMine ? 'items-end' : 'items-start' }}">
                            <div class="max-w-[80%] p-3 rounded-2xl space-y-1 {{ $isMine ? 'bg-teal-800 text-white rounded-br-none' : 'bg-white border border-slate-200 text-slate-900 rounded-bl-none' }}">
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
                    <input type="hidden" name="sender_role" value="ADMIN">
                    <input type="text" name="message" required placeholder="Ketik pesan ke Customer..." class="flex-1 px-3 py-2 rounded-xl border border-slate-300">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-teal-700 text-white font-bold">Kirim</button>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endsection