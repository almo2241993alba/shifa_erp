<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_return_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('sale_return_id')->constrained('sale_returns')->onDelete('cascade');
            $table->foreignId('sale_payment_id')->constrained('sale_payments')->onDelete('restrict');
            $table->foreignId('treasury_id')->constrained('treasuries')->onDelete('restrict');
            $table->decimal('amount', 15, 4);
            $table->timestamps();

            $table->unique(['sale_return_id', 'sale_payment_id']);
            $table->index('sale_payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_payments');
    }
};
