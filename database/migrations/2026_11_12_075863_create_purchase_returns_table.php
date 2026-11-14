<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('financial_transaction_id')->unique()->constrained('financial_transactions')->onDelete('restrict');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignUuid('purchase_id')->constrained('purchases')->onDelete('restrict'); // فاتورة الشراء الأصلية
            $table->foreignId('supplier_id')->constrained('contacts')->onDelete('restrict');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('treasury_id')->nullable()->constrained('treasuries')->onDelete('restrict'); // في حال استرداد المبلغ نقداً/بنكياً
            $table->string('return_number')->unique(); // رقم إشعار مرتجع المشتريات (PRET-2026-0001)
            $table->date('return_date');
            $table->decimal('total_return_amount', 15, 4);
            $table->decimal('tax_return_amount', 15, 4)->default(0.0000);
            $table->text('reason'); // سبب الإرجاع للمورد (تلف، قرب انتهاء صلاحية، خطأ توريد)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
