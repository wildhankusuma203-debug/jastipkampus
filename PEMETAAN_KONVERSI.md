# TABEL PEMETAAN KONVERSI FITUR REACT (TSX) KE LARAVEL 11 + MYSQL

Tidak ada satu pun fitur dari file sumber React (`jastip_kampus_upn.tsx`) maupun fitur penyempurnaan yang dihilangkan. Seluruhnya telah dikonversi ke arsitektur MVC Laravel + MySQL berikut:

| No | Fitur / Komponen React Asli | Implementasi di Laravel 11 + MySQL | File Tujuan di Proyek Laravel | Status |
| :- | :--- | :--- | :--- | :--- |
| 1 | Inisialisasi Firebase & Status Koneksi Database Jujur[cite: 1] | Koneksi MySQL Eloquent ORM & Indikator Bar Status Header | `.env.example`, `resources/views/layouts/app.blade.php` | Selesai 100% |
| 2 | `formatIDR`, `formatWIB`, `sanitizeInput`[cite: 1] | Helper PHP terpusat dengan `Carbon` (`Asia/Jakarta`) & `strip_tags` | `app/Helpers/FormatHelper.php` | Selesai 100% |
| 3 | `VALID_ORDER_TRANSITIONS` & Status Maps[cite: 1] | Method `canTransitionOrder()`, `orderStatusMap()`, `escrowStatusMap()` | `app/Helpers/FormatHelper.php` | Selesai 100% |
| 4 | Data Master `INITIAL_FACULTIES`, `INITIAL_STUDY_PROGRAMS`, `INITIAL_CLASSES`[cite: 1] | Migration Tabel Akademik & Seeder Master Data Contoh | `database/seeders/DatabaseSeeder.php` | Selesai 100% |
| 5 | Verifikasi Password & Sesi Autentikasi[cite: 1] | `Hash::make()` (Bcrypt), `Hash::check()`, Laravel Session Guard | `app/Http/Controllers/Auth/AuthController.php` | Selesai 100% |
| 6 | Setup Awal Administrator Utama Pertama (`SYSADMIN_INIT`)[cite: 1] | Method `initFirstSysAdmin()` dengan kunci pengecekan eksistensi `SYSADMIN` | `app/Http/Controllers/Auth/AuthController.php` | Selesai 100% |
| 7 | Guard RBAC `hasRole()` & `trySwitchDashboard()`[cite: 1] | Server-side Middleware `RoleMiddleware` & Multi-Role Pivot `role_user` | `app/Http/Middleware/RoleMiddleware.php`, `app/Models/User.php` | Selesai 100% |
| 8 | `PublicAuthenticationGate` & Empty State Layanan[cite: 1] | `PublicController@index` & View Blade Landing Page | `app/Http/Controllers/PublicController.php`, `resources/views/public/landing.blade.php` | Selesai 100% |
| 9 | `CustomerDashboardWorkspace` (9 Menu Navigasi)[cite: 1] | `CustomerDashboardController@index` & View Blade Customer | `app/Http/Controllers/Customer/CustomerDashboardController.php`, `resources/views/customer/dashboard.blade.php` | Selesai 100% |
| 10 | Filter Layanan Lintas Fakultas, Prodi, Kelas, Kategori & Harga[cite: 1] | Query Builder Eloquent dengan filter parameter `GET` | `app/Http/Controllers/Customer/CustomerDashboardController.php` | Selesai 100% |
| 11 | Permintaan Jastip Bebas + Input Patokan Harga Customer (`customer_budget`) | Validasi & penyimpanan `customer_budget` di `CustomerOrderController@store` | `app/Http/Controllers/Customer/CustomerOrderController.php` | Selesai 100% |
| 12 | Respon Nego Harga & Tolak Nego Tanpa Menghapus Pesanan | `CustomerOrderController@respondOffer` mengembalikan status ke `WAITING_OFFER` | `app/Http/Controllers/Customer/CustomerOrderController.php` | Selesai 100% |
| 13 | Simulasi Rekening Bersama (`EscrowPaymentModal` & `handlePayEscrow`) | `EscrowController@pay` dengan `DB::transaction` & pencatatan tabel `payments` | `app/Http/Controllers/Customer/EscrowController.php` | Selesai 100% |
| 14 | Konfirmasi Pesanan Selesai oleh Customer & Pencairan Dana ke Admin | `CustomerOrderController@confirmCompletion` mencairkan dana ke `wallet_balance` Admin | `app/Http/Controllers/Customer/CustomerOrderController.php` | Selesai 100% |
| 15 | Ulasan & Rating 1–5 Bintang (1 Ulasan per Pesanan `COMPLETED`)[cite: 1] | `CustomerDashboardController@submitReview` + Constraint `unique('order_id')` | `app/Http/Controllers/Customer/CustomerDashboardController.php` | Selesai 100% |
| 16 | Upload KTM Calon Admin + SHA-256 Checksum[cite: 1] | `hash_file('sha256')` & penyimpanan ke `storage/app/private/ktm_vault` | `app/Http/Controllers/Customer/KtmApplicationController.php` | Selesai 100% |
| 17 | `AdminDashboardWorkspace` & Pencairan Saldo Dompet Escrow | `AdminDashboardController@index` & `withdrawBalance()` | `app/Http/Controllers/Admin/AdminDashboardController.php`, `resources/views/admin/dashboard.blade.php` | Selesai 100% |
| 18 | CRUD Layanan Milik Admin & Proteksi Kepemilikan (`ownerAdminId`)[cite: 1] | `AdminServiceController@save` & `@toggleStatus` | `app/Http/Controllers/Admin/AdminServiceController.php` | Selesai 100% |
| 19 | Ambil Patokan Harga Customer / Kirim Nego Harga oleh Admin | `AdminOrderController@acceptCustomerBudget` & `@sendOffer` | `app/Http/Controllers/Admin/AdminOrderController.php` | Selesai 100% |
| 20 | Upload Foto Bukti Struk (`RECEIPT`) & Bukti Penyerahan (`DELIVERY`) | `AdminOrderController@uploadProof` ke disk `public` | `app/Http/Controllers/Admin/AdminOrderController.php` | Selesai 100% |
| 21 | Ruang Chat Pesanan In-App (`OrderChatModal`) | `OrderChatController@sendMessage` + Modal Chat Alpine.js | `app/Http/Controllers/OrderChatController.php` | Selesai 100% |
| 22 | `SysAdminDashboardWorkspace` (Verifikasi KTM Privat, Master Akademik, Moderasi, Laporan, Audit Log)[cite: 1] | `SysAdminController` + `viewPrivateKtm()` Stream Response | `app/Http/Controllers/SysAdmin/SysAdminController.php`, `resources/views/sysadmin/dashboard.blade.php` | Selesai 100% |
| 23 | Simulasi Lingkungan QA (`provisionQaTestEnvironment`)[cite: 1] | `QaEnvironmentController` (`toggleMode`, `provisionQaAccounts`, `quickSwitch`) | `app/Http/Controllers/QaEnvironmentController.php` | Selesai 100% |
| 24 | Suite Pengujian Wajib Otomatis[cite: 1] | 8 Method Feature Test PHPUnit mencakup 17 skenario wajib | `tests/Feature/JastipKampusTest.php` | Selesai 100% |