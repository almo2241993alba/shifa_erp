<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table): void {
            $table->foreignId('closing_treasury_id')->nullable()->after('treasury_id')->constrained('treasuries')->restrictOnDelete();
            $table->decimal('cash_transfer_amount', 15, 4)->default(0)->after('actual_closing_cash');
            $table->decimal('reserve_amount_at_close', 15, 4)->nullable()->after('cash_transfer_amount');
            $table->foreignUuid('closing_journal_entry_id')->nullable()->after('reserve_amount_at_close')->constrained('journal_entries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('closing_journal_entry_id');
            $table->dropConstrainedForeignId('closing_treasury_id');
            $table->dropColumn(['cash_transfer_amount', 'reserve_amount_at_close']);
        });
    }
};
