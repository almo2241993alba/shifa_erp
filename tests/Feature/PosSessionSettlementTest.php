<?php

namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Models\PosSession;
use App\Models\Treasury;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosSessionSettlementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.initial_admin_password' => 'secure-test-password-2026']);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_session_opens_only_on_a_cash_drawer_and_reconciles_its_opening_count(): void
    {
        $user = User::query()->where('role', 'admin')->firstOrFail();
        $user->forceFill(['email_verified_at' => now()])->save();
        $bank = Treasury::query()->where('branch_id', $user->branch_id)->where('type', 'bank')->firstOrFail();
        $drawer = Treasury::query()->where('branch_id', $user->branch_id)->where('type', 'cash_drawer')->firstOrFail();

        $this->actingAs($user)->postJson('/pharmacy/pos-sessions/open', [
            'treasury_id' => $bank->id,
            'opening_cash' => 80,
        ])->assertUnprocessable()->assertJsonValidationErrors('treasury_id');

        $response = $this->actingAs($user)->postJson('/pharmacy/pos-sessions/open', [
            'treasury_id' => $drawer->id,
            'opening_cash' => 80,
        ])->assertCreated();

        $this->assertSame('80.0000', $response->json('data.expected_cash'));
        $this->assertSame(80.0, (float) $drawer->refresh()->current_balance);
    }

    public function test_closing_transfers_cash_above_the_drawer_reserve_to_the_main_safe_with_a_balanced_entry(): void
    {
        $user = User::query()->where('role', 'admin')->firstOrFail();
        $user->forceFill(['email_verified_at' => now()])->save();
        $drawer = Treasury::query()->where('branch_id', $user->branch_id)->where('type', 'cash_drawer')->firstOrFail();
        $safe = Treasury::query()->where('branch_id', $user->branch_id)->where('type', 'main_safe')->firstOrFail();
        $drawer->update(['drawer_reserve_amount' => 25]);
        $session = PosSession::query()->create([
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'treasury_id' => $drawer->id,
            'opened_at' => now(),
            'opening_cash' => 100,
            'expected_cash' => 100,
            'status' => 'open',
        ]);

        $response = $this->actingAs($user)->postJson("/pharmacy/pos-sessions/{$session->id}/close", [
            'actual_closing_cash' => 150,
            'closing_treasury_id' => $safe->id,
        ])->assertOk();

        $closed = $session->refresh();
        $entry = JournalEntry::query()->with('lines')->findOrFail($closed->closing_journal_entry_id);
        $this->assertSame(50.0, (float) $closed->difference);
        $this->assertSame(125.0, (float) $closed->cash_transfer_amount);
        $this->assertSame(25.0, (float) $closed->reserve_amount_at_close);
        $this->assertSame(25.0, (float) $drawer->refresh()->current_balance);
        $this->assertSame(125.0, (float) $safe->refresh()->current_balance);
        $this->assertSame((float) $entry->total_debit, (float) $entry->total_credit);
        $this->assertSame($entry->id, $response->json('data.closing_journal_entry_id'));
    }
}
