<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('financial_transaction_id')->unique()->constrained('financial_transactions')->onDelete('restrict');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignUuid('sale_id')->constrained('sales')->onDelete('restrict'); // الفاتورة الأصلية
            $table->foreignUuid('pos_session_id')->constrained('pos_sessions')->onDelete('restrict'); // الجلسة التي تم فيها الإرجاع
            $table->foreignUuid('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('treasury_id')->constrained('treasuries')->onDelete('restrict'); // الخزينة التي رُدّ منها المبلغ
            $table->string('return_number')->unique(); // رقم إشعار المرتجع (RET-2026-0001)
            $table->decimal('total_refund_amount', 15, 4); // إجمالي المبلغ المسترد للعميل
            $table->decimal('tax_refund_amount', 15, 4)->default(0.0000);
            $table->text('reason'); // سبب الإرجاع الإلزامي
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_returns');
    }
};
