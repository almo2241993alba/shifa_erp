<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('purchase_return_id')->constrained('purchase_returns')->onDelete('cascade');
            $table->foreignId('purchase_item_id')->constrained('purchase_items')->onDelete('restrict');
            $table->foreignUuid('medicine_id')->constrained('medicines')->onDelete('restrict');
            $table->foreignId('unit_id')->constrained('units')->onDelete('restrict');
            $table->foreignUuid('batch_id')->constrained('batches')->onDelete('restrict'); // التشغيلة التي ستُخصم منها الكمية المرتجعة للمورد
            $table->integer('returned_quantity');
            $table->integer('base_quantity_deducted');
            $table->decimal('unit_purchase_price', 15, 4); // سعر الشراء التاريخي من المورد
            $table->decimal('tax_return_amount', 15, 4)->default(0.0000);
            $table->decimal('line_total', 15, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
    }
};
