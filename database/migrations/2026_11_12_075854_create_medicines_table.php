<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('category_id')->constrained('categories')->onDelete('restrict');
            $table->foreignId('dosage_form_id')->constrained('dosage_forms')->onDelete('restrict');
            $table->string('trade_name'); // الاسم التجاري (مثل: Panadol Extra)
            $table->string('scientific_name')->nullable(); // الاسم العلمي / المادة الفعالة (Paracetamol)
            $table->string('barcode')->nullable()->unique(); // الباركود الدولي أو الداخلي
            $table->string('manufacturer')->nullable(); // الشركة المصنعة
            $table->enum('item_type', ['medicine', 'medical_supply', 'cosmetic'])->default('medicine'); // دواء، مستلزم طبي (سرنجة)، تجميل
            $table->enum('dispensing_method', ['FEFO', 'FIFO'])->default('FEFO'); // نظام الصرف الافتراضي
            $table->integer('min_stock_alert')->default(10); // حد التنبيه للنواقص بالوحدة الأساسية الصغرى
            $table->boolean('is_taxable')->default(true); // هل يخضع لضريبة القيمة المضافة
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
