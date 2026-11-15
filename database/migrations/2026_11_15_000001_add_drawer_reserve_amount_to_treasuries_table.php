<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treasuries', function (Blueprint $table): void {
            $table->decimal('drawer_reserve_amount', 15, 4)->default(0)->after('current_balance');
        });
    }

    public function down(): void
    {
        Schema::table('treasuries', function (Blueprint $table): void {
            $table->dropColumn('drawer_reserve_amount');
        });
    }
};
