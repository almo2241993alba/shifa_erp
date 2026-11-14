<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('restrict'); // الكاشير أو الصيدلي المسؤول
            $table->foreignId('treasury_id')->constrained('treasuries')->onDelete('restrict'); // درج الكاشير المرتبط بالجلسة
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_cash', 15, 4)->default(0.0000); // رصيد العهدة الافتتاحي
            $table->decimal('expected_cash', 15, 4)->default(0.0000); // الرصيد المتوقع حسابياً عند الإغلاق
            $table->decimal('actual_closing_cash', 15, 4)->nullable(); // الجرد الفعلي المدخل بيد الموظف
            $table->decimal('difference', 15, 4)->default(0.0000); // الفارق (سالب = عجز، موجب = زيادة)
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sessions');
    }
};
