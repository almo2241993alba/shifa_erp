<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->boolean('is_insurance_provider')->default(false)->after('is_employee');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->decimal('discount_approval_limit', 5, 2)->default(5.00);
        });

        Schema::create('insurance_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurer_contact_id')->constrained('contacts')->onDelete('restrict');
            $table->string('name');
            $table->string('code');
            $table->decimal('coverage_percentage', 5, 2)->default(100.00);
            $table->decimal('patient_copay_percentage', 5, 2)->default(0.00);
            $table->decimal('max_coverage_per_claim', 15, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['insurer_contact_id', 'code']);
        });

        Schema::create('insurance_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_contact_id')->constrained('contacts')->onDelete('restrict');
            $table->foreignId('insurance_plan_id')->constrained('insurance_plans')->onDelete('restrict');
            $table->string('policy_number')->unique();
            $table->string('member_number')->nullable();
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_contact_id', 'is_active']);
        });

        Schema::create('insurance_plan_medicines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_plan_id')->constrained('insurance_plans')->onDelete('cascade');
            $table->foreignUuid('medicine_id')->constrained('medicines')->onDelete('restrict');
            $table->boolean('is_covered')->default(true);
            $table->decimal('coverage_percentage', 5, 2)->nullable();
            $table->decimal('patient_copay_percentage', 5, 2)->nullable();
            $table->decimal('max_coverage_amount', 15, 4)->nullable();
            $table->timestamps();

            $table->unique(['insurance_plan_id', 'medicine_id']);
        });

        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('sale_id')->unique()->constrained('sales')->onDelete('restrict');
            $table->foreignId('insurance_policy_id')->constrained('insurance_policies')->onDelete('restrict');
            $table->foreignId('insurer_contact_id')->constrained('contacts')->onDelete('restrict');
            $table->foreignId('patient_contact_id')->constrained('contacts')->onDelete('restrict');
            $table->foreignUuid('submitted_by')->constrained('users')->onDelete('restrict');
            $table->foreignUuid('processed_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->string('claim_number')->unique();
            $table->decimal('claimed_amount', 15, 4);
            $table->decimal('approved_amount', 15, 4)->nullable();
            $table->decimal('paid_amount', 15, 4)->default(0);
            $table->enum('status', ['pending', 'approved', 'partially_approved', 'rejected', 'paid'])->default('pending');
            $table->string('insurer_reference')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('insurance_policy_id')->nullable()->after('customer_id')
                ->constrained('insurance_policies')->onDelete('restrict');
            $table->decimal('patient_due', 15, 4)->default(0)->after('grand_total');
            $table->decimal('insurance_due', 15, 4)->default(0)->after('patient_due');
            $table->string('discount_reason')->nullable()->after('discount_amount');
            $table->foreignUuid('discounted_by_user_id')->nullable()->after('discount_reason')
                ->constrained('users')->onDelete('restrict');
            $table->foreignUuid('discount_approved_by_user_id')->nullable()->after('discounted_by_user_id')
                ->constrained('users')->onDelete('restrict');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('discount_amount', 15, 4)->default(0)->after('unit_price');
            $table->string('discount_reason')->nullable()->after('discount_amount');
            $table->foreignUuid('discounted_by_user_id')->nullable()->after('discount_reason')
                ->constrained('users')->onDelete('restrict');
            $table->foreignUuid('discount_approved_by_user_id')->nullable()->after('discounted_by_user_id')
                ->constrained('users')->onDelete('restrict');
            $table->enum('free_source', ['none', 'pharmacy', 'insurance'])->default('none')->after('is_accessory_free');
            $table->decimal('insurance_covered_amount', 15, 4)->default(0)->after('free_source');
            $table->decimal('patient_amount', 15, 4)->default(0)->after('insurance_covered_amount');
        });

        Schema::table('sale_returns', function (Blueprint $table) {
            $table->decimal('patient_refund_amount', 15, 4)->default(0)->after('total_refund_amount');
            $table->decimal('insurance_refund_amount', 15, 4)->default(0)->after('patient_refund_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropColumn(['patient_refund_amount', 'insurance_refund_amount']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['discounted_by_user_id']);
            $table->dropForeign(['discount_approved_by_user_id']);
            $table->dropColumn([
                'discount_amount',
                'discount_reason',
                'discounted_by_user_id',
                'discount_approved_by_user_id',
                'free_source',
                'insurance_covered_amount',
                'patient_amount',
            ]);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['insurance_policy_id']);
            $table->dropForeign(['discounted_by_user_id']);
            $table->dropForeign(['discount_approved_by_user_id']);
            $table->dropColumn([
                'insurance_policy_id',
                'patient_due',
                'insurance_due',
                'discount_reason',
                'discounted_by_user_id',
                'discount_approved_by_user_id',
            ]);
        });

        Schema::dropIfExists('insurance_claims');
        Schema::dropIfExists('insurance_plan_medicines');
        Schema::dropIfExists('insurance_policies');
        Schema::dropIfExists('insurance_plans');

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('discount_approval_limit');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('is_insurance_provider');
        });
    }
};
