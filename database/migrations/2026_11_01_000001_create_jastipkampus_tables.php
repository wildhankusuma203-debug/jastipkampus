<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. TABEL FACULTIES
        Schema::create('faculties', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 120);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        // 2. TABEL STUDY_PROGRAMS
        Schema::create('study_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('faculties')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('degree', 15)->default('S1');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        // 3. TABEL CLASSES
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_program_id')->constrained('study_programs')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('cohort_year', 10)->default('2024');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        // 4. TABEL ROLES
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30)->unique();
            $table->string('label', 80);
            $table->timestamps();
        });

        // 5. TABEL USERS
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->string('university', 150)->default('UPN "Veteran" Yogyakarta');
            $table->foreignId('faculty_id')->nullable()->constrained('faculties')->nullOnDelete();
            $table->foreignId('study_program_id')->nullable()->constrained('study_programs')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->string('nim', 40)->nullable();
            $table->string('phone', 30)->nullable();
            $table->bigInteger('wallet_balance')->default(0);
            $table->boolean('show_class_publicly')->default(true);
            $table->boolean('is_suspended')->default(false)->index();
            $table->enum('admin_verification_status', [
                'NOT_SUBMITTED',
                'PENDING',
                'VERIFIED',
                'REJECTED'
            ])->default('NOT_SUBMITTED')->index();
            $table->rememberToken();
            $table->timestamps();
        });

        // 6. TABEL ROLE_USER
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'role_id']);
        });

        // 7. TABEL SYSTEM_CONFIGS
        Schema::create('system_configs', function (Blueprint $table) {
            $table->id();
            $table->string('platform_name', 80)->default('JastipKampus UPNYK');
            $table->boolean('allow_cross_faculty_orders')->default(true);
            $table->boolean('require_ktm_for_admin')->default(true);
            $table->unsignedTinyInteger('max_ktm_size_mb')->default(2);
            $table->timestamps();
        });

        // 8. TABEL ADMIN_APPLICATIONS
        Schema::create('admin_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('full_name', 120);
            $table->string('email', 150);
            $table->string('university', 150);
            $table->foreignId('faculty_id')->nullable()->constrained('faculties')->nullOnDelete();
            $table->foreignId('study_program_id')->nullable()->constrained('study_programs')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->string('nim', 40);
            $table->string('ktm_file_name', 150);
            $table->string('ktm_file_type', 60);
            $table->unsignedInteger('ktm_file_size');
            $table->string('ktm_checksum', 64);
            $table->string('ktm_private_path', 255);
            $table->enum('status', ['PENDING', 'VERIFIED', 'REJECTED'])->default('PENDING')->index();
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rejection_reason', 255)->nullable();
            $table->timestamps();
        });

        // 9. TABEL CATEGORIES
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 10. TABEL SERVICES (Dilengkapi requirement_file_path untuk upload PDF syarat dari Admin)
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_admin_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('name', 150);
            $table->text('description');
            $table->bigInteger('price');
            $table->string('requirements', 300)->nullable();
            $table->string('requirement_file_path', 255)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_published')->default(true)->index();
            $table->foreignId('faculty_id')->nullable()->constrained('faculties')->nullOnDelete();
            $table->foreignId('study_program_id')->nullable()->constrained('study_programs')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->boolean('show_class_publicly')->default(true);
            $table->timestamps();
        });

        // 11. TABEL ORDERS (Dilengkapi supporting_file_path untuk upload PDF dari Customer)
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 40)->unique();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('owner_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('customer_faculty_id')->nullable()->constrained('faculties')->nullOnDelete();
            $table->foreignId('customer_program_id')->nullable()->constrained('study_programs')->nullOnDelete();
            $table->foreignId('customer_class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->boolean('is_custom_request')->default(false)->index();
            $table->string('title', 150);
            $table->text('description');
            $table->string('items_detail', 300);
            $table->string('pickup_location', 220);
            $table->string('delivery_location', 220);
            $table->dateTime('deadline');
            $table->string('notes', 300)->nullable();
            $table->string('supporting_info', 300)->nullable();
            $table->string('supporting_file_path', 255)->nullable();
            $table->bigInteger('customer_budget')->default(0);
            $table->bigInteger('offered_price')->nullable();
            $table->string('offer_note', 255)->nullable();
            $table->bigInteger('agreed_price')->nullable();
            $table->enum('status', [
                'WAITING_REVIEW',
                'WAITING_OFFER',
                'WAITING_CUSTOMER_APPROVAL',
                'APPROVED_FOR_PROCESS',
                'IN_PROGRESS',
                'WAITING_CUSTOMER_COMPLETION',
                'COMPLETED',
                'CANCELLED'
            ])->default('WAITING_OFFER')->index();
            $table->enum('escrow_status', [
                'UNPAID',
                'HELD_IN_ESCROW',
                'RELEASED_TO_ADMIN',
                'REFUNDED'
            ])->default('UNPAID')->index();
            $table->string('escrow_payment_method', 80)->nullable();
            $table->dateTime('escrow_paid_at')->nullable();
            $table->string('receipt_proof_path', 255)->nullable();
            $table->string('delivery_proof_path', 255)->nullable();
            $table->timestamps();
        });

        // 12. TABEL ORDER_HISTORIES
        Schema::create('order_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name', 120);
            $table->string('status', 50);
            $table->text('note');
            $table->timestamps();
        });

        // 13. TABEL ORDER_CHATS
        Schema::create('order_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->string('sender_name', 120);
            $table->string('sender_role', 30);
            $table->text('message');
            $table->timestamps();
        });

        // 14. TABEL PAYMENTS
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['ESCROW_DEPOSIT', 'ESCROW_RELEASE', 'ESCROW_REFUND', 'WALLET_WITHDRAWAL'])->index();
            $table->bigInteger('amount');
            $table->string('payment_method', 80)->nullable();
            $table->string('bank_or_ewallet', 80)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('status', 30)->default('SUCCESS');
            $table->timestamps();
        });

        // 15. TABEL REVIEWS
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('owner_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment');
            $table->timestamps();
        });

        // 16. TABEL NOTIFICATIONS
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('related_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('title', 150);
            $table->string('message', 300);
            $table->boolean('is_read')->default(false)->index();
            $table->timestamps();
        });

        // 17. TABEL REPORTS
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('reporter_name', 120);
            $table->string('target_title', 150);
            $table->text('reason');
            $table->enum('status', ['OPEN', 'RESOLVED'])->default('OPEN')->index();
            $table->string('resolution_note', 255)->nullable();
            $table->foreignId('resolved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 18. TABEL AUDIT_LOGS
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name', 120);
            $table->string('actor_role', 60);
            $table->string('action', 80)->index();
            $table->string('target_id', 80)->nullable();
            $table->string('detail', 255);
            $table->timestamps();
        });

        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_chats');
        Schema::dropIfExists('order_histories');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('services');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('admin_applications');
        Schema::dropIfExists('system_configs');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('study_programs');
        Schema::dropIfExists('faculties');
    }
};