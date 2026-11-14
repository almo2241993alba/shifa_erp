<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('purchase_id')->constrained('purchases')->onDelete('cascade');
            $table->foreignUuid('medicine_id')->constrained('medicines')->onDelete('restrict');
            $table->foreignId('unit_id')->constrained('units')->onDelete('restrict'); // وحدة الشراء (كرتون غالباً)
            $table->foreignUuid('batch_id')->nullable()->constrained('batches')->onDelete('restrict'); // التشغيلة الناتجة في المخزن
            $table->string('batch_number'); // رقم التشغيلة
            $table->date('production_date')->nullable(); // تاريخ الإنتاج
            $table->date('expiry_date'); // تاريخ الانتهاء الإلزامي
            $table->integer('quantity'); // الكمية المشتراة بوحدة الشراء (مثلاً 5 كراتين)
            $table->integer('bonus_quantity')->default(0); // كمية البونص المجانية من المورد (إن وجدت)
            $table->integer('total_base_quantity'); // إجمالي الكمية المحولة لأصغر وحدة (base unit)
            $table->decimal('unit_purchase_price', 15, 4); // سعر شراء الكرتون من المورد
            $table->decimal('new_selling_price', 15, 4)->nullable(); // يُملأ فقط إذا تغير سعر البيع فيُحدّث السعر الأساسي في medicine_units
            $table->decimal('tax_amount', 15, 4)->default(0.0000);
            $table->decimal('allocated_additional_cost', 15, 4)->default(0.0000); // نصيب هذا الصنف من الشحن والعمالة
            $table->decimal('landed_cost_per_base_unit', 15, 4); // التكلفة الفعلية للوحدة الصغرى واصلة للمخزن
            $table->decimal('line_total', 15, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
