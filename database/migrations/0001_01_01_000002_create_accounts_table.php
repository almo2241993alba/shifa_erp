<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->onDelete('restrict'); // الحساب الأب للشجرة الهرمية
            $table->string('code')->unique(); // رقم الحساب المحاسبي (مثل: 1101)
            $table->string('name'); // اسم الحساب (الصندوق، البنك، المخزون، المبيعات...)
            $table->enum('account_type', ['asset', 'liability', 'equity', 'revenue', 'expense']); // طبيعة الحساب
            $table->boolean('is_transactional')->default(true); // هل يقبل قيود مباشرة أم حساب رئيسي تجميعي فقط
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
