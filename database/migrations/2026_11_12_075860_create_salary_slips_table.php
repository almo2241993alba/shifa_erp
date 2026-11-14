<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_slips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignUuid('employee_id')->constrained('employees')->onDelete('restrict');
            $table->foreignUuid('financial_transaction_id')->unique()->constrained('financial_transactions')->onDelete('restrict');
            $table->foreignId('treasury_id')->nullable()->constrained('treasuries')->onDelete('restrict'); // الخزينة التي صُرف منها الراتب
            $table->string('year_month_key', 10); // مفتاح الشهر مثل (11-2026) لمنع تكرار المسير
            $table->decimal('calculated_basic_salary', 15, 4); // الراتب الأساسي المقفل وقت الإصدار
            $table->decimal('calculated_fixed_allowances', 15, 4)->default(0.0000);
            $table->decimal('total_commissions_earned', 15, 4)->default(0.0000);
            $table->decimal('total_overtime_pay', 15, 4)->default(0.0000);
            $table->decimal('amount_bonus', 15, 4)->default(0.0000); // مكافآت إضافية
            $table->decimal('total_deductions_fines', 15, 4)->default(0.0000); // الاستقطاعات والسلف وعجز درج الكاشير
            $table->decimal('previous_balance_carried_forward', 15, 4)->default(0.0000); // المستحقات المرحلة من الشهر السابق
            $table->decimal('gross_deserved_amount', 15, 4); // الصافي الإجمالي المستحق
            $table->decimal('actual_paid_amount', 15, 4)->default(0.0000); // المبلغ المدفوع فعلياً
            $table->decimal('balance_remaining', 15, 4)->default(0.0000); // المتبقي للموظف (يُرحل للشهر القادم)
            $table->enum('payout_status', ['paid', 'partially_paid', 'unpaid'])->default('unpaid');
            $table->enum('payment_method', ['cash', 'bank_transfer'])->nullable();
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'employee_id', 'year_month_key'], 'branch_employee_salary_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_slips');
    }
};
