<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('pharmacy_name');
            $table->string('logo')->nullable();
            $table->string('tax_number', 20)->nullable();
            $table->decimal('tax_rate', 5, 2)->default(15.00); // نسبة ضريبة القيمة المضافة الافتراضية
            $table->string('currency_code', 10)->default('SAR'); // رمز العملة (SAR / SDG)
            $table->integer('min_expiry_alert_days')->default(90); // التنبيه قبل انتهاء الصلاحية بـ 90 يوماً
            // ربط الحسابات الافتراضية لتوليد القيود المحاسبية آلياً
            $table->foreignId('sales_account_id')->nullable()->constrained('accounts')->onDelete('restrict');
            $table->foreignId('inventory_account_id')->nullable()->constrained('accounts')->onDelete('restrict');
            $table->foreignId('cogs_account_id')->nullable()->constrained('accounts')->onDelete('restrict'); // تكلفة البضاعة المباعة
            $table->foreignId('vat_payable_account_id')->nullable()->constrained('accounts')->onDelete('restrict'); // ضريبة المخرجات
            $table->foreignId('vat_receivable_account_id')->nullable()->constrained('accounts')->onDelete('restrict'); // ضريبة المدخلات
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
