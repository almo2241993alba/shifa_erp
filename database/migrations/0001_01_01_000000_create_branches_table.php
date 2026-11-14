<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم الفرع (مثل: الفرع الرئيسي)
            $table->string('code')->unique(); // كود الفرع (BR-001)
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('tax_number', 20)->nullable(); // الرقم الضريبي للفرع
            $table->boolean('is_main')->default(false); // تحديد الفرع الرئيسي
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
