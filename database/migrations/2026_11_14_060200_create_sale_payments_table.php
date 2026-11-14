<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('sale_id')->constrained('sales')->onDelete('restrict');
            $table->foreignId('treasury_id')->nullable()->constrained('treasuries')->onDelete('restrict');
            $table->enum('payment_method', ['cash', 'card', 'credit']);
            $table->decimal('amount', 15, 4);
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->index(['sale_id', 'payment_method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
