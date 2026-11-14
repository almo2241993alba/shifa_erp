<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('financial_transaction_id')->unique()->constrained('financial_transactions')->onDelete('restrict');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('restrict');
            $table->string('entry_number')->unique(); // رقم القيد المحاسبي (JE-2026-0001)
            $table->date('entry_date');
            $table->string('description'); // شرح القيد (مثل: قيد مبيعات فاتورة رقم INV-2026-0001)
            $table->decimal('total_debit', 15, 4); // إجمالي المدين
            $table->decimal('total_credit', 15, 4); // إجمالي الدائن (يجب أن يساوي المدين دائماً)
            $table->boolean('is_auto_generated')->default(true); // هل القيد مولد آلياً من النظام أم يدوي من المحاسب
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
