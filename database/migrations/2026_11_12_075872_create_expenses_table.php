<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('financial_transaction_id')->unique()->constrained('financial_transactions')->onDelete('restrict');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('expense_type_id')->constrained('expense_types')->onDelete('restrict');
            $table->foreignId('treasury_id')->constrained('treasuries')->onDelete('restrict'); // الخزينة التي خُصم منها المبلغ
            $table->foreignUuid('pos_session_id')->nullable()->constrained('pos_sessions')->onDelete('restrict'); // يُملأ إذا صُرف من درج الكاشير أثناء الوردية
            $table->foreignUuid('user_id')->constrained('users')->onDelete('restrict');
            $table->decimal('amount', 15, 4); // المبلغ الصافي قبل الضريبة
            $table->decimal('amount_tax', 15, 4)->default(0.0000); // ضريبة المدخلات (15%) المستردة في المقاصة الضريبية
            $table->date('expense_date');
            $table->text('notes'); // البيان التوضيحي الإلزامي لحماية العهدة
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
