<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->onDelete('restrict'); // الحساب المقابل في شجرة الحسابات
            $table->string('type_name')->unique(); // مثل: كهرباء ومياه، إيجار الصيدلية، أكياس وتغليف، تراخيص
            $table->enum('expense_category', ['operational', 'administrative', 'marketing', 'maintenance'])->default('operational');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_types');
    }
};
