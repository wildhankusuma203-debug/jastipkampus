<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Customer\CustomerDashboardController;
use App\Http\Controllers\Customer\CustomerOrderController;
use App\Http\Controllers\Customer\EscrowController;
use App\Http\Controllers\Customer\KtmApplicationController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminServiceController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\SysAdmin\SysAdminController;
use App\Http\Controllers\OrderChatController;
use App\Http\Controllers\QaEnvironmentController;

// 1. GERBANG PUBLIK
Route::get('/', [PublicController::class, 'index'])->name('public.landing');

// 2. AUTENTIKASI & SETUP AWAL SYSADMIN
Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
Route::post('/auth/register', [AuthController::class, 'registerCustomer'])->name('auth.register');
Route::post('/auth/init-sysadmin', [AuthController::class, 'initFirstSysAdmin'])->name('auth.init.sysadmin');
Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

// 3. KONTROL LINGKUNGAN SIMULASI QA
Route::post('/qa/toggle-mode', [QaEnvironmentController::class, 'toggleMode'])->name('qa.toggle');
Route::post('/qa/provision', [QaEnvironmentController::class, 'provisionQaAccounts'])->name('qa.provision');
Route::post('/qa/switch', [QaEnvironmentController::class, 'quickSwitch'])->name('qa.switch');

// 4. NOTIFIKASI (Tandai Dibaca -> Langsung Hilang dari Daftar Aktif)
Route::middleware(['auth'])->group(function () {
    Route::post('/notifications/{notification}/read', [CustomerDashboardController::class, 'markNotificationRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [CustomerDashboardController::class, 'markAllNotificationsRead'])->name('notifications.read-all');
});

// 5. WORKSPACE 1: CUSTOMER DASHBOARD
Route::middleware(['auth', 'role:CUSTOMER'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');
    Route::post('/profile', [CustomerDashboardController::class, 'updateProfile'])->name('profile.update');
    Route::post('/orders', [CustomerOrderController::class, 'store'])->name('orders.store');
    Route::post('/orders/{order}/respond-offer', [CustomerOrderController::class, 'respondOffer'])->name('orders.respond-offer');
    Route::post('/orders/{order}/cancel', [CustomerOrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/confirm-completion', [CustomerOrderController::class, 'confirmCompletion'])->name('orders.confirm-completion');
    Route::post('/orders/{order}/pay-escrow', [EscrowController::class, 'pay'])->name('orders.pay-escrow');
    Route::post('/orders/{order}/review', [CustomerDashboardController::class, 'submitReview'])->name('orders.review');
    Route::post('/ktm-application', [KtmApplicationController::class, 'store'])->name('ktm.store');
    Route::post('/report', [CustomerDashboardController::class, 'submitReport'])->name('report.store');
});

// 6. WORKSPACE 2: ADMIN DASHBOARD
Route::middleware(['auth', 'role:ADMIN'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::post('/categories', [AdminDashboardController::class, 'storeCategory'])->name('categories.store');
    Route::delete('/categories/{category}', [AdminDashboardController::class, 'destroyCategory'])->name('categories.destroy');
    Route::post('/wallet/withdraw', [AdminDashboardController::class, 'withdrawBalance'])->name('wallet.withdraw');
    Route::post('/services', [AdminServiceController::class, 'save'])->name('services.save');
    Route::post('/services/{service}/toggle', [AdminServiceController::class, 'toggleStatus'])->name('services.toggle');
    Route::post('/orders/{order}/accept-budget', [AdminOrderController::class, 'acceptCustomerBudget'])->name('orders.accept-budget');
    Route::post('/orders/{order}/send-offer', [AdminOrderController::class, 'sendOffer'])->name('orders.send-offer');
    Route::post('/orders/{order}/decline', [AdminOrderController::class, 'decline'])->name('orders.decline');
    Route::post('/orders/{order}/upload-proof', [AdminOrderController::class, 'uploadProof'])->name('orders.upload-proof');
    Route::post('/orders/{order}/update-status', [AdminOrderController::class, 'updateStatus'])->name('orders.update-status');
});

// 7. WORKSPACE 3: ADMINISTRATOR UTAMA
Route::middleware(['auth', 'role:SYSADMIN'])->prefix('sysadmin')->name('sysadmin.')->group(function () {
    Route::get('/dashboard', [SysAdminController::class, 'index'])->name('dashboard');
    Route::get('/ktm-vault/{application}', [SysAdminController::class, 'viewPrivateKtm'])->name('ktm.view');
    Route::post('/admin-applications/{application}/verify', [SysAdminController::class, 'verifyAdminApplication'])->name('applications.verify');
    Route::post('/users/{user}/toggle-suspend', [SysAdminController::class, 'toggleSuspendUser'])->name('users.toggle-suspend');
    Route::post('/faculties', [SysAdminController::class, 'saveFaculty'])->name('faculties.save');
    Route::post('/faculties/{faculty}/toggle', [SysAdminController::class, 'toggleFaculty'])->name('faculties.toggle');
    Route::post('/programs', [SysAdminController::class, 'saveProgram'])->name('programs.save');
    Route::post('/programs/{program}/toggle', [SysAdminController::class, 'toggleProgram'])->name('programs.toggle');
    Route::post('/classes', [SysAdminController::class, 'saveClass'])->name('classes.save');
    Route::post('/classes/{academicClass}/toggle', [SysAdminController::class, 'toggleClass'])->name('classes.toggle');
    Route::post('/services/{service}/moderate', [SysAdminController::class, 'moderateService'])->name('services.moderate');
    Route::post('/reports/{report}/resolve', [SysAdminController::class, 'resolveReport'])->name('reports.resolve');
});

// 8. RUANG CHAT PESANAN IN-APP
Route::middleware(['auth'])->post('/orders/{order}/chat', [OrderChatController::class, 'sendMessage'])->name('orders.chat.send');