<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignId('customer_account_id')->nullable()->after('account_id')
                ->constrained('accounts')->onDelete('restrict');
            $table->foreignId('supplier_account_id')->nullable()->after('customer_account_id')
                ->constrained('accounts')->onDelete('restrict');
        });

        DB::table('contacts')
            ->where('is_customer', true)
            ->whereNotNull('account_id')
            ->update(['customer_account_id' => DB::raw('account_id')]);

        $supplierAccountId = DB::table('accounts')->where('code', '2101')->value('id');

        if ($supplierAccountId !== null) {
            DB::table('contacts')
                ->where('is_supplier', true)
                ->whereNull('supplier_account_id')
                ->update(['supplier_account_id' => $supplierAccountId]);
        }
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_account_id');
            $table->dropConstrainedForeignId('customer_account_id');
        });
    }
};
