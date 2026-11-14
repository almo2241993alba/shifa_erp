<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignUuid('medicine_id')->constrained('medicines')->onDelete('restrict');
            $table->string('batch_number'); // رقم التشغيلة القادم من المصنع
            $table->date('production_date')->nullable(); // تاريخ الإنتاج
            $table->date('expiry_date')->index(); // تاريخ انتهاء الصلاحية (مفهرس لتسريع استعلامات FEFO)
            $table->date('received_date'); // تاريخ دخول التشغيلة للمخزن (مهم لنظام FIFO)
            $table->integer('initial_quantity_base')->default(0); // الكمية الواردة بأصغر وحدة
            $table->integer('remaining_quantity_base')->default(0); // الرصيد المتبقي الفعلي بأصغر وحدة
            $table->decimal('unit_purchase_price', 15, 4); // سعر شراء الوحدة الصغرى من المورد فقط
            $table->decimal('landed_cost_price', 15, 4); // التكلفة الفعلية للوحدة الصغرى شاملة الشحن والعمالة
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['medicine_id', 'expiry_date', 'remaining_quantity_base'], 'fefo_stock_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
