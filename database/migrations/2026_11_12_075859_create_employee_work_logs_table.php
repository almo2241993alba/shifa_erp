<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_work_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignUuid('employee_id')->constrained('employees')->onDelete('restrict');
            $table->date('work_date');
            $table->decimal('worked_shifts', 4, 1)->default(1.0); // يقبل كسور الوردية (0.5 أو 1.0)
            $table->decimal('hours_worked', 5, 2)->default(8.00); // ساعات العمل الفعلية
            $table->decimal('overtime_hours', 5, 2)->default(0.00); // ساعات العمل الإضافية
            $table->decimal('drawer_discrepancy', 15, 4)->default(0.0000); // فارق عهدة الكاشير المرحل (السالب يخصم كعجز)
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_work_logs');
    }
};
