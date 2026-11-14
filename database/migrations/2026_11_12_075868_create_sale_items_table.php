<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('sale_id')->constrained('sales')->onDelete('cascade');
            $table->foreignUuid('medicine_id')->constrained('medicines')->onDelete('restrict');
            $table->foreignId('unit_id')->constrained('units')->onDelete('restrict'); // الوحدة المباعة (علبة، شريط، قطعة)
            $table->foreignUuid('batch_id')->constrained('batches')->onDelete('restrict'); // التشغيلة المسحوبة بنظام FEFO/FIFO
            $table->integer('quantity'); // الكمية بالوحدة المباعة
            $table->integer('base_quantity_deducted'); // الكمية المخصومة فعلياً من المخزن بأصغر وحدة
            $table->decimal('unit_price', 15, 4); // سعر البيع للوحدة (يكون 0 إذا كان ملحقاً مجانياً)
            $table->decimal('landed_cost_at_sale', 15, 4); // التكلفة الواصلة الفعلية للكمية المباعة لحساب صافي الربح بدقة
            $table->decimal('tax_amount', 15, 4)->default(0.0000);
            $table->decimal('line_total', 15, 4);
            $table->boolean('is_accessory_free')->default(false); // هل هذا الصنف ملحق مجاني (مثل سرنجة مع حقنة)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
