<?php

namespace Tests\Feature\Auth;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_new_users_can_register(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register');

        $component->assertRedirect(route('login', absolute: false));

        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'cashier',
            'is_active' => false,
            'registration_status' => 'pending',
        ]);
    }

    public function test_admin_can_approve_a_registration_and_assign_branch_and_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
        $pendingUser = User::factory()->create([
            'branch_id' => null,
            'role' => 'cashier',
            'is_active' => false,
            'registration_status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('pharmacy.registration-requests.index'))
            ->assertOk()
            ->assertSee($pendingUser->email);

        $this->post(route('pharmacy.registration-requests.approve', $pendingUser), [
            'branch_id' => $branch->id,
            'role' => 'pharmacist',
        ])->assertRedirect(route('pharmacy.registration-requests.index'));

        $this->assertDatabaseHas('users', [
            'id' => $pendingUser->id,
            'branch_id' => $branch->id,
            'role' => 'pharmacist',
            'is_active' => true,
            'registration_status' => 'approved',
        ]);
    }

    public function test_non_admin_cannot_approve_registration_requests(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($user)
            ->get(route('pharmacy.registration-requests.index'))
            ->assertForbidden();
    }
}
