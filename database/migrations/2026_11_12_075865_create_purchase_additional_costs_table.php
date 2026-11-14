<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_additional_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('purchase_id')->constrained('purchases')->onDelete('cascade');
            $table->foreignId('treasury_id')->constrained('treasuries')->onDelete('restrict'); // الخزينة التي دُفع منها المصروف
            $table->string('cost_type'); // مثل: شحن، نقل داخلي، أجور عمال تحميل وتنزيل، تخليص
            $table->decimal('amount', 15, 4); // قيمة المصروف
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_additional_costs');
    }
};
