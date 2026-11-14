<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('journal_entry_id')->constrained('journal_entries')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('accounts')->onDelete('restrict'); // الحساب من شجرة الحسابات
            $table->decimal('debit', 15, 4)->default(0.0000); // المبلغ المدين
            $table->decimal('credit', 15, 4)->default(0.0000); // المبلغ الدائن
            $table->string('memo')->nullable(); // بيان تفصيلي للسطر
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
    }
};
