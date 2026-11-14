<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('contact_id')->unique()->constrained('contacts')->onDelete('restrict'); // ربط فريد بملف جهة الاتصال
            $table->string('employee_number')->unique(); // الرقم الوظيفي (EMP-2026-001)
            $table->string('national_id')->unique(); // رقم الهوية أو الإقامة
            $table->enum('job_title', ['manager', 'pharmacist', 'assistant_pharmacist', 'cashier', 'accountant', 'storekeeper', 'cleaner']);
            $table->string('license_number')->nullable(); // رقم ترخيص مزاولة المهنة للصيدلي
            $table->date('license_expiry')->nullable(); // تاريخ انتهاء الترخيص للتنبيه المبكر
            $table->enum('salary_type', ['monthly', 'daily', 'shift'])->default('monthly'); // نظام احتساب الراتب
            $table->decimal('salary_basic', 15, 4); // الراتب الأساسي أو قيمة اليومية/الوردية
            $table->decimal('allowances_fixed', 15, 4)->default(0.0000); // البدلات الثابتة
            $table->decimal('commission_rate', 5, 2)->default(0.00); // نسبة عمولة اختيارية على المبيعات المستهدفة
            $table->date('hire_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
