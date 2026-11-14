<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('restrict'); // الحساب المالي للعميل أو المورد
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('tax_number', 20)->nullable()->unique();
            $table->enum('contact_type', ['B2C', 'B2B'])->default('B2C'); // أفراد أم شركات/مؤسسات
            $table->boolean('is_customer')->default(false);
            $table->boolean('is_supplier')->default(false);
            $table->boolean('is_employee')->default(false);
            $table->decimal('credit_limit', 15, 4)->default(0.0000); // حد الائتمان للعملاء الآجل
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
