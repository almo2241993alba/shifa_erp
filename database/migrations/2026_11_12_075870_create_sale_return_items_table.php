<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('sale_return_id')->constrained('sale_returns')->onDelete('cascade');
            $table->foreignId('sale_item_id')->constrained('sale_items')->onDelete('restrict'); // ربط بالسطر الأصلي لمنع إرجاع كمية أكبر من المباعة
            $table->foreignUuid('medicine_id')->constrained('medicines')->onDelete('restrict');
            $table->foreignId('unit_id')->constrained('units')->onDelete('restrict');
            $table->foreignUuid('batch_id')->constrained('batches')->onDelete('restrict'); // التشغيلة التي ستُعاد إليها الكمية
            $table->integer('returned_quantity'); // الكمية المرتجعة بوحدة البيع
            $table->integer('base_quantity_restored'); // الكمية المعادة للمخزن بأصغر وحدة
            $table->decimal('unit_price_at_sale', 15, 4); // السعر التاريخي الذي باع به الكاشير فعلاً
            $table->decimal('tax_refund_amount', 15, 4)->default(0.0000);
            $table->decimal('line_total', 15, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
    }
};
