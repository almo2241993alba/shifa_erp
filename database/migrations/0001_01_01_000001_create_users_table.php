<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary(); // معرف UUID صلب لحماية الحسابات
            $table->unsignedBigInteger('branch_id')->nullable()->index(); // الفرع النشط للمستخدم
            $table->unsignedBigInteger('contact_id')->nullable()->unique(); // ربط اختياري بملف جهة الاتصال
            $table->string('name'); // الاسم الكامل للعرض
            $table->string('username')->unique(); // اسم المستخدم لتسجيل الدخول السريع للكاشير
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['admin', 'pharmacist', 'cashier', 'accountant', 'storekeeper'])->default('cashier'); // الدور الوظيفي
            $table->json('permissions')->nullable(); // صلاحيات إضافية مرنة عند الحاجة
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index()->constrained('users')->onDelete('cascade'); // متوافق مع UUID
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
