<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.initial_admin_password' => 'secure-test-password-2026']);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_locale_switch_is_kept_in_the_session_and_updates_direction_and_translations(): void
    {
        $user = User::query()->where('role', 'admin')->firstOrFail();
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($user)->post('/locale', [
            'locale' => 'en',
            'return_to' => '/profile',
        ])->assertRedirect('/profile');

        $this->get('/profile')
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Account profile');
    }
}
