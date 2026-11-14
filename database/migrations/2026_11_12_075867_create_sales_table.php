<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('financial_transaction_id')->unique()->constrained('financial_transactions')->onDelete('restrict');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignUuid('pos_session_id')->constrained('pos_sessions')->onDelete('restrict'); // إلزامي: لا بيع بدون جلسة مفتوحة
            $table->foreignUuid('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('customer_id')->nullable()->constrained('contacts')->onDelete('restrict'); // العميل من جدول contacts
            $table->foreignId('treasury_id')->nullable()->constrained('treasuries')->onDelete('restrict'); // الخزينة أو البنك المستلم للمبلغ
            $table->string('invoice_number')->unique(); // الرقم التسلسلي العام على مستوى النظام (INV-2026-0001)
            $table->unsignedInteger('session_receipt_number'); // رقم الفاتورة داخل الجلسة (يبدأ من 1 في كل جلسة جديدة)
            $table->decimal('subtotal', 15, 4); // الإجمالي قبل الضريبة والخصم
            $table->decimal('discount_amount', 15, 4)->default(0.0000);
            $table->decimal('tax_amount', 15, 4)->default(0.0000); // ضريبة المخرجات (15%)
            $table->decimal('grand_total', 15, 4); // الإجمالي النهائي
            $table->decimal('paid_amount', 15, 4);
            $table->decimal('change_amount', 15, 4)->default(0.0000); // الباقي المرتجع للعميل
            $table->enum('payment_method', ['cash', 'card', 'credit', 'mixed'])->default('cash'); // كاش، شبكة، آجل، مختلط
            $table->enum('status', ['completed', 'returned', 'partially_returned'])->default('completed');
            $table->text('notes')->nullable();
            $table->timestamps();

            // قيد أمان يمنع تكرار رقم الإيصال داخل نفس الجلسة
            $table->unique(['pos_session_id', 'session_receipt_number'], 'session_receipt_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
