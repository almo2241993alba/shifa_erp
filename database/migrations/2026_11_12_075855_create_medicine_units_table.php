<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_units', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('medicine_id')->constrained('medicines')->onDelete('cascade');
            $table->foreignId('unit_id')->constrained('units')->onDelete('restrict');
            $table->integer('conversion_factor')->default(1); // معامل التحويل لأصغر وحدة (مثلاً: شريط=1، علبة=3، كرتون=60)
            $table->decimal('selling_price', 15, 4); // سعر البيع لهذه الوحدة
            $table->string('unit_barcode')->nullable()->unique(); // باركود خاص بالعلبة أو الشريط إذا اختلف
            $table->boolean('is_base_unit')->default(false); // هل هي أصغر وحدة يُخزن بها الرصيد في المخزن
            $table->boolean('is_purchasable')->default(false); // متاحة للشراء (مثل الكرتون)
            $table->boolean('is_sellable')->default(true); // متاحة للبيع في الكاشير (تمنع تجزئة الحقن/اللبوس إذا عُطلت)
            $table->timestamps();

            $table->unique(['medicine_id', 'unit_id']); // منع تكرار نفس الوحدة لنفس الدواء
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_units');
    }
};
