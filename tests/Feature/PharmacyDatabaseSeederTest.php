<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\FinancialTransaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class PharmacyDatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.initial_admin_password' => 'secure-test-password-2026']);
    }

    public function test_database_seeder_builds_single_branch_foundation_idempotently(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('branches', 1);
        $this->assertDatabaseCount('accounts', 16);
        $this->assertDatabaseCount('treasuries', 3);
        $this->assertDatabaseCount('categories', 9);
        $this->assertDatabaseCount('dosage_forms', 8);
        $this->assertDatabaseCount('units', 7);
        $this->assertDatabaseCount('expense_types', 6);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('medicines', 0);

        $contact = Contact::where('name', 'عميل ومورد نقدي عام')->firstOrFail();
        $this->assertTrue($contact->is_customer);
        $this->assertTrue($contact->is_supplier);
        $this->assertNotNull($contact->customer_account_id);
        $this->assertNotNull($contact->supplier_account_id);

        $admin = User::where('email', 'admin@shifa.com')->firstOrFail();
        $this->assertTrue(Hash::check('secure-test-password-2026', $admin->password));
    }

    public function test_uuid_models_generate_string_primary_keys_and_payment_table_exists(): void
    {
        $transaction = FinancialTransaction::create();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $transaction->id
        );
        $this->assertTrue(Schema::hasColumns('sale_payments', [
            'sale_id',
            'treasury_id',
            'payment_method',
            'amount',
            'reference',
        ]));
    }

    public function test_model_fillable_attributes_and_relationships_match_the_schema(): void
    {
        $relationshipCount = 0;

        foreach (glob(app_path('Models/*.php')) as $file) {
            $class = 'App\\Models\\' . pathinfo($file, PATHINFO_FILENAME);
            $model = new $class();
            $columns = Schema::getColumnListing($model->getTable());

            foreach ($model->getFillable() as $attribute) {
                $this->assertContains($attribute, $columns, "{$class} has unknown fillable attribute {$attribute}");
            }

            foreach (get_class_methods($model) as $methodName) {
                $method = new ReflectionMethod($class, $methodName);
                $returnType = $method->getReturnType();

                if (
                    $method->getDeclaringClass()->getName() !== $class
                    || !$method->isPublic()
                    || !$returnType
                    || !is_a($returnType->getName(), Relation::class, true)
                ) {
                    continue;
                }

                $relation = $method->invoke($model);
                $this->assertInstanceOf(Relation::class, $relation, "{$class}::{$methodName} is not a relation");
                $this->assertTrue(
                    Schema::hasTable($relation->getRelated()->getTable()),
                    "{$class}::{$methodName} targets a missing table"
                );
                $relationshipCount++;
            }
        }

        $this->assertGreaterThan(100, $relationshipCount);
    }
}
