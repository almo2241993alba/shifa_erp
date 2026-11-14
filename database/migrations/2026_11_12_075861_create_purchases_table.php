<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('financial_transaction_id')->unique()->constrained('financial_transactions')->onDelete('restrict');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('supplier_id')->constrained('contacts')->onDelete('restrict');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('treasury_id')->nullable()->constrained('treasuries')->onDelete('restrict');
            $table->string('purchase_number')->unique();
            $table->string('supplier_invoice_number')->nullable();
            $table->date('purchase_date');
            $table->date('received_date');
            $table->decimal('subtotal', 15, 4);
            $table->decimal('tax_amount', 15, 4)->default(0.0000);
            $table->decimal('additional_costs_total', 15, 4)->default(0.0000);
            $table->decimal('discount_amount', 15, 4)->default(0.0000);
            $table->decimal('grand_total', 15, 4);
            $table->decimal('paid_amount', 15, 4)->default(0.0000);
            $table->decimal('remaining_amount', 15, 4)->default(0.0000);
            $table->enum('payment_status', ['paid', 'partially_paid', 'unpaid'])->default('unpaid');
            $table->enum('status', ['draft', 'received', 'cancelled'])->default('received');
            $table->text('notes')->nullable();
            $table->timestamps();// الخزينة في حال الدفع الفوري
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
