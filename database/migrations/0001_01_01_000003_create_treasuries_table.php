<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treasuries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('account_id')->constrained('accounts')->onDelete('restrict'); // الحساب المقابل في شجرة الحسابات
            $table->string('name'); // مثل: درج الكاشير 1، الخزنة الرئيسية، بنك الراجحي
            $table->enum('type', ['cash_drawer', 'main_safe', 'bank']);
            $table->decimal('current_balance', 15, 4)->default(0.0000); // الرصيد الفعلي اللحظي
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treasuries');
    }
};
