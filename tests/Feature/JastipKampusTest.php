<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AdminApplication;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Faculty;
use App\Models\Order;
use App\Models\Role;
use App\Models\Service;
use App\Models\StudyProgram;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JastipKampusTest extends TestCase
{
    use RefreshDatabase;

    protected Faculty $facIlkom;
    protected Faculty $facFeb;
    protected StudyProgram $prdIf;
    protected StudyProgram $prdMnj;
    protected AcademicClass $clsIfA;
    protected AcademicClass $clsMnjA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->facIlkom = Faculty::where('code', 'FTI')->firstOrFail();
        $this->facFeb   = Faculty::where('code', 'FEB')->firstOrFail();
        $this->prdIf    = StudyProgram::where('name', 'Informatika')->firstOrFail();
        $this->prdMnj   = StudyProgram::where('name', 'Manajemen')->firstOrFail();
        $this->clsIfA   = AcademicClass::where('name', 'Informatika A')->firstOrFail();
        $this->clsMnjA  = AcademicClass::where('name', 'Manajemen A')->firstOrFail();
    }

    /**
     * 1 & 4. Uji Registrasi Customer & Pastikan Tidak Bisa Membuat SYSADMIN dari Registrasi Biasa.
     */
    public function test_customer_registration_assigns_only_customer_role(): void
    {
        $response = $this->post(route('auth.register'), [
            'name'             => 'Budi Mahasiswa',
            'email'            => 'budi@kampus.ac.id',
            'password'         => 'Password#123',
            'university'       => 'Universitas Nusantara',
            'faculty_id'       => $this->facIlkom->id,
            'study_program_id' => $this->prdIf->id,
            'class_id'         => $this->clsIfA->id,
            'nim'              => '2410511001',
            'phone'            => '08123456789',
        ]);

        $response->assertRedirect(route('customer.dashboard'));
        $user = User::where('email', 'budi@kampus.ac.id')->firstOrFail();

        $this->assertTrue($user->hasRole('CUSTOMER'));
        $this->assertFalse($user->hasRole('ADMIN'));
        $this->assertFalse($user->hasRole('SYSADMIN'));
    }

    /**
     * 5. Uji Validasi Relasi Berjenjang Fakultas -> Program Studi -> Kelas.
     */
    public function test_registration_rejects_mismatched_faculty_program_and_class(): void
    {
        // Fakultas Ilmu Komputer tetapi memilih Program Studi Manajemen (FEB)
        $response = $this->post(route('auth.register'), [
            'name'             => 'Mahasiswa Salah Pilih',
            'email'            => 'salah@kampus.ac.id',
            'password'         => 'Password#123',
            'faculty_id'       => $this->facIlkom->id,
            'study_program_id' => $this->prdMnj->id,
            'class_id'         => $this->clsIfA->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['email' => 'salah@kampus.ac.id']);
    }

    /**
     * 2. Uji Login Kredensial Benar, Penolakan Password Salah, dan Logout.
     */
    public function test_login_and_logout_flow(): void
    {
        $custRole = Role::where('name', 'CUSTOMER')->firstOrFail();
        $user = User::create([
            'name'             => 'Siti Customer',
            'email'            => 'siti@kampus.ac.id',
            'password'         => Hash::make('Rahasia#2026'),
            'faculty_id'       => $this->facIlkom->id,
            'study_program_id' => $this->prdIf->id,
            'class_id'         => $this->clsIfA->id,
        ]);
        $user->roles()->attach($custRole->id);

        // Password salah harus ditolak
        $badLogin = $this->post(route('auth.login'), [
            'email'    => 'siti@kampus.ac.id',
            'password' => 'PasswordSalah',
        ]);
        $badLogin->assertSessionHas('error');
        $this->assertGuest();

        // Password benar harus berhasil masuk
        $goodLogin = $this->post(route('auth.login'), [
            'email'    => 'siti@kampus.ac.id',
            'password' => 'Rahasia#2026',
        ]);
        $goodLogin->assertRedirect(route('customer.dashboard'));
        $this->assertAuthenticatedAs($user);

        // Logout
        $this->post(route('auth.logout'))->assertRedirect(route('public.landing'));
        $this->assertGuest();
    }

    /**
     * 4B. Uji Inisialisasi Pertama SYSADMIN Hanya Bisa Dilakukan Satu Kali.
     */
    public function test_first_sysadmin_initialization_can_only_run_once(): void
    {
        $res1 = $this->post(route('auth.init.sysadmin'), [
            'name'     => 'Dr. Hendra SysAdmin',
            'email'    => 'sysadmin@kampus.ac.id',
            'password' => 'SysAdminStrong#2026',
        ]);
        $res1->assertRedirect(route('sysadmin.dashboard'));

        $this->post(route('auth.logout'));

        // Percobaan kedua oleh pihak lain harus ditolak
        $res2 = $this->post(route('auth.init.sysadmin'), [
            'name'     => ' Penyusup SysAdmin',
            'email'    => 'hacker@kampus.ac.id',
            'password' => 'HackerStrong#2026',
        ]);
        $res2->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['email' => 'hacker@kampus.ac.id']);
    }

    /**
     * 3. Uji Proteksi Dashboard Berdasarkan Role (RBAC Middleware).
     */
    public function test_role_middleware_blocks_unauthorized_dashboard_access(): void
    {
        $custRole = Role::where('name', 'CUSTOMER')->firstOrFail();
        $customer = User::create([
            'name'                      => 'Customer Biasa',
            'email'                     => 'cust@kampus.ac.id',
            'password'                  => Hash::make('Password#123'),
            'admin_verification_status' => 'NOT_SUBMITTED',
        ]);
        $customer->roles()->attach($custRole->id);

        $this->actingAs($customer);

        // Customer biasa mencoba buka Admin Dashboard -> Ditolak & diarahkan ke form KTM
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('customer.dashboard', ['menu' => 'CUST_REGISTER_ADMIN']));

        // Customer biasa mencoba buka SysAdmin Dashboard -> Ditolak
        $this->get(route('sysadmin.dashboard'))
            ->assertRedirect(route('customer.dashboard'));
    }

    /**
     * 6 & 16. Uji Upload KTM Privat, Sanitasi Audit Log (Tanpa NIM), & Verifikasi oleh SysAdmin.
     */
    public function test_ktm_upload_and_sysadmin_verification_grants_admin_role(): void
    {
        Storage::fake('local');

        $custRole = Role::where('name', 'CUSTOMER')->firstOrFail();
        $sysRole  = Role::where('name', 'SYSADMIN')->firstOrFail();

        $customer = User::create([
            'name'             => 'Calon Admin',
            'email'            => 'calon@kampus.ac.id',
            'password'         => Hash::make('Password#123'),
            'university'       => 'Universitas Nusantara',
            'faculty_id'       => $this->facIlkom->id,
            'study_program_id' => $this->prdIf->id,
            'class_id'         => $this->clsIfA->id,
        ]);
        $customer->roles()->attach($custRole->id);

        $sysAdmin = User::create([
            'name'                      => 'Otoritas SysAdmin',
            'email'                     => 'root@kampus.ac.id',
            'password'                  => Hash::make('SysAdmin#12345'),
            'admin_verification_status' => 'VERIFIED',
        ]);
        $sysAdmin->roles()->attach($sysRole->id);

        // Customer mengunggah berkas fisik KTM
        $this->actingAs($customer);
        $ktmFile = UploadedFile::fake()->image('ktm_mahasiswa.jpg', 600, 400)->size(500);

        $this->post(route('customer.ktm.store'), [
            'full_name' => 'Calon Admin Lengkap',
            'nim'       => '2410511099',
            'ktm_file'  => $ktmFile,
        ])->assertRedirect(route('customer.dashboard', ['menu' => 'CUST_REGISTER_ADMIN']));

        $app = AdminApplication::where('user_id', $customer->id)->firstOrFail();
        $this->assertEquals('PENDING', $app->status);
        Storage::disk('local')->assertExists($app->ktm_private_path);

        // Pastikan NIM tidak bocor di Audit Log
        $audit = AuditLog::where('action', 'SUBMIT_ADMIN_APPLICATION')->firstOrFail();
        $this->assertStringNotContainsString('2410511099', $audit->detail);

        // SysAdmin menyetujui pengajuan KTM
        $this->actingAs($sysAdmin);
        $this->post(route('sysadmin.applications.verify', $app), [
            'decision' => 'VERIFIED',
        ])->assertSessionHas('success');

        $customer->refresh();
        $this->assertTrue($customer->isVerifiedAdmin());
    }

    /**
     * 7 & 8. Uji Publikasi Layanan, Visibilitas Publik, dan Larangan Edit Layanan Admin Lain.
     */
    public function test_service_publication_visibility_and_ownership_protection(): void
    {
        $admRole = Role::where('name', 'ADMIN')->firstOrFail();
        $custRole = Role::where('name', 'CUSTOMER')->firstOrFail();

        $admin1 = User::create([
            'name'                      => 'Admin Satu',
            'email'                     => 'adm1@kampus.ac.id',
            'password'                  => Hash::make('Password#123'),
            'admin_verification_status' => 'VERIFIED',
        ]);
        $admin1->roles()->attach([$custRole->id, $admRole->id]);

        $admin2 = User::create([
            'name'                      => 'Admin Dua',
            'email'                     => 'adm2@kampus.ac.id',
            'password'                  => Hash::make('Password#123'),
            'admin_verification_status' => 'VERIFIED',
        ]);
        $admin2->roles()->attach([$custRole->id, $admRole->id]);

        $cat = Category::create(['name' => 'Dokumen Akademik', 'created_by' => $admin1->id, 'is_active' => true]);

        // Admin 1 membuat layanan NONAKTIF
        $this->actingAs($admin1);
        $this->post(route('admin.services.save'), [
            'name'        => 'Jastip Rahasia Nonaktif',
            'category_id' => $cat->id,
            'description' => 'Layanan belum dipublikasikan',
            'price'       => 20000,
            'is_active'   => false,
        ]);

        $service = Service::where('owner_admin_id', $admin1->id)->firstOrFail();

        // Layanan nonaktif tidak boleh muncul di Landing Page publik
        $this->post(route('auth.logout'));
        $this->get(route('public.landing'))->assertDontSee('Jastip Rahasia Nonaktif');

        // Admin 2 mencoba mengedit layanan milik Admin 1 -> Harus ditolak!
        $this->actingAs($admin2);
        $this->post(route('admin.services.save'), [
            'service_id'  => $service->id,
            'name'        => 'Dibajak Admin Dua',
            'category_id' => $cat->id,
            'description' => 'Bajakan',
            'price'       => 99000,
            'is_active'   => true,
        ])->assertSessionHas('error');

        $this->assertEquals('Jastip Rahasia Nonaktif', $service->fresh()->name);
    }

    /**
     * 9, 10, 11, 12, 13, 14, 15. Uji Siklus Lengkap:
     * Patokan Harga Customer -> Nego Admin -> Tolak Nego (Kembali ke Bursa) -> Setuju Harga ->
     * Setor Escrow -> Chat In-App -> Upload Bukti Foto -> Customer Konfirmasi Selesai & Cairkan Dana -> Review.
     */
    public function test_full_order_lifecycle_budget_negotiation_escrow_and_customer_completion(): void
    {
        Storage::fake('public');

        $custRole = Role::where('name', 'CUSTOMER')->firstOrFail();
        $admRole  = Role::where('name', 'ADMIN')->firstOrFail();

        $customer = User::create([
            'name'             => 'Wildhan Customer',
            'email'            => 'wildhan@kampus.ac.id',
            'password'         => Hash::make('Password#123'),
            'faculty_id'       => $this->facIlkom->id,
            'study_program_id' => $this->prdIf->id,
            'class_id'         => $this->clsIfA->id,
        ]);
        $customer->roles()->attach($custRole->id);

        $admin1 = User::create([
            'name'                      => 'Admin Pen 못',
            'email'                     => 'admin1@kampus.ac.id',
            'password'                  => Hash::make('Password#123'),
            'admin_verification_status' => 'VERIFIED',
            'wallet_balance'            => 0,
        ]);
        $admin1->roles()->attach([$custRole->id, $admRole->id]);

        $admin2 = User::create([
            'name'                      => 'Admin Kedua',
            'email'                     => 'admin2@kampus.ac.id',
            'password'                  => Hash::make('Password#123'),
            'admin_verification_status' => 'VERIFIED',
            'wallet_balance'            => 0,
        ]);
        $admin2->roles()->attach([$custRole->id, $admRole->id]);

        // 1. Customer membuat permintaan bebas dengan Patokan Harga Rp 15.000
        $this->actingAs($customer);
        $this->post(route('customer.orders.store'), [
            'service_id'        => 'CUSTOM',
            'title'             => 'Titip Ambil KTM di BAAK',
            'description'       => 'Tolong ambilkan KTM saya di loket BAAK lantai 1',
            'items_detail'      => '1 Kartu Tanda Mahasiswa',
            'pickup_location'   => 'Gedung Rektorat BAAK',
            'delivery_location' => 'Asrama Mahasiswa',
            'deadline'          => now()->addDays(2)->format('Y-m-d\TH:i'),
            'customer_budget'   => 15000,
        ])->assertRedirect(route('customer.dashboard', ['menu' => 'CUST_MY_ORDERS']));

        $order = Order::where('customer_id', $customer->id)->firstOrFail();
        $this->assertEquals(15000, $order->customer_budget);
        $this->assertEquals('WAITING_OFFER', $order->status);

        // 2. Admin 1 mengajukan Nego Harga Rp 35.000 (kemahalan)
        $this->actingAs($admin1);
        $this->post(route('admin.orders.send-offer', $order), [
            'offered_price' => 35000,
            'offer_note'    => 'Antrean panjang sekali',
        ]);
        $this->assertEquals('WAITING_CUSTOMER_APPROVAL', $order->fresh()->status);

        // 3. Customer menolak Nego Admin 1 -> Pesanan TIDAK terhapus, tapi kembali ke WAITING_OFFER untuk Admin lain!
        $this->actingAs($customer);
        $this->post(route('customer.orders.respond-offer', $order), [
            'is_approved' => false,
        ]);
        $order->refresh();
        $this->assertEquals('WAITING_OFFER', $order->status);
        $this->assertNull($order->owner_admin_id);

        // 4. Admin 2 mengambil pesanan & langsung menyetujui Patokan Harga Customer (Rp 15.000)
        $this->actingAs($admin2);
        $this->post(route('admin.orders.accept-budget', $order));
        $order->refresh();
        $this->assertEquals('APPROVED_FOR_PROCESS', $order->status);
        $this->assertEquals(15000, $order->agreed_price);
        $this->assertEquals($admin2->id, $order->owner_admin_id);

        // 5. Admin 2 mencoba mulai memproses pesanan SEBELUM Customer membayar Escrow -> Harus Ditolak!
        $this->post(route('admin.orders.update-status', $order), [
            'next_status' => 'IN_PROGRESS',
        ])->assertSessionHas('error');
        $this->assertEquals('APPROVED_FOR_PROCESS', $order->fresh()->status);

        // 6. Customer menyetor Rp 15.000 ke Rekening Bersama (Escrow)
        $this->actingAs($customer);
        $this->post(route('customer.orders.pay-escrow', $order), [
            'payment_method' => 'QRIS Kampus',
        ]);
        $this->assertEquals('HELD_IN_ESCROW', $order->fresh()->escrow_status);

        // 7. Customer dan Admin 2 berdiskusi via Chat In-App
        $this->post(route('orders.chat.send', $order), [
            'message'     => 'Halo kak, surat kuasa sudah siap ya.',
            'sender_role' => 'CUSTOMER',
        ]);
        $this->assertDatabaseHas('order_chats', [
            'order_id' => $order->id,
            'message'  => 'Halo kak, surat kuasa sudah siap ya.',
        ]);

        // 8. Admin 2 memproses pesanan (IN_PROGRESS) dan mengunggah Foto Bukti Penyerahan
        $this->actingAs($admin2);
        $this->post(route('admin.orders.update-status', $order), [
            'next_status' => 'IN_PROGRESS',
        ]);
        $this->assertEquals('IN_PROGRESS', $order->fresh()->status);

        $proofImage = UploadedFile::fake()->image('bukti_serah_ktm.jpg');
        $this->post(route('admin.orders.upload-proof', $order), [
            'proof_type' => 'DELIVERY',
            'proof_file' => $proofImage,
        ]);
        $this->assertNotNull($order->fresh()->delivery_proof_path);

        // 9. Admin 2 mengonfirmasi barang diserahkan (WAITING_CUSTOMER_COMPLETION)
        $this->post(route('admin.orders.update-status', $order), [
            'next_status' => 'WAITING_CUSTOMER_COMPLETION',
        ]);
        $this->assertEquals('WAITING_CUSTOMER_COMPLETION', $order->fresh()->status);
        $this->assertEquals(0, $admin2->fresh()->wallet_balance); // Dana belum cair sebelum Customer klik selesai

        // 10. Customer menekan tombol "Pesanan Selesai" -> Status COMPLETED & Dana Escrow Rp 15.000 cair ke Saldo Admin 2!
        $this->actingAs($customer);
        $this->post(route('customer.orders.confirm-completion', $order));
        $order->refresh();
        $admin2->refresh();

        $this->assertEquals('COMPLETED', $order->status);
        $this->assertEquals('RELEASED_TO_ADMIN', $order->escrow_status);
        $this->assertEquals(15000, $admin2->wallet_balance);

        // 11. Customer memberikan ulasan bintang 5
        $this->post(route('customer.orders.review', $order), [
            'rating'  => 5,
            'comment' => 'Sangat cepat dan amanah!',
        ]);
        $this->assertDatabaseHas('reviews', [
            'order_id' => $order->id,
            'rating'   => 5,
        ]);
    }

    /**
     * 17. Uji Penangguhan Akun (Suspend) Memblokir Akses Pengguna.
     */
    public function test_suspended_user_is_blocked_by_middleware(): void
    {
        $custRole = Role::where('name', 'CUSTOMER')->firstOrFail();
        $user = User::create([
            'name'         => 'Akun Bermasalah',
            'email'        => 'suspend@kampus.ac.id',
            'password'     => Hash::make('Password#123'),
            'is_suspended' => true,
        ]);
        $user->roles()->attach($custRole->id);

        $this->actingAs($user);
        $this->get(route('customer.dashboard'))
            ->assertRedirect(route('public.landing'))
            ->assertSessionHas('error');
    }
}