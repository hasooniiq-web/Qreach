<?php

namespace Tests\Feature\Api;

use App\Models\Admin;
use App\Models\Payment;
use App\Models\Referral;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\PlanEntitlements;
use App\Services\ReferralService;
use App\Services\SubscriptionManager;
use App\Services\VoucherService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Invite a friend: the inviter is given free Plus days when an invited friend makes a
 * first paid activation — once per friend, capped per inviter, never for a trial.
 */
class ReferralTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();
        RateLimiter::clear('x');
    }

    private function plus(): SubscriptionPlan
    {
        return SubscriptionPlan::where('slug', 'plus')->firstOrFail();
    }

    private function codeOf(User $user): string
    {
        return app(ReferralService::class)->codeFor($user);
    }

    private function planOf(User $user): string
    {
        return app(PlanEntitlements::class)->planFor($user->fresh())->slug;
    }

    /** The friend pays for Plus with a voucher (the customer-initiated paid path that needs no gateway). */
    private function friendPays(User $friend): void
    {
        $batch = app(VoucherService::class)->generate($this->plus(), 1, 365, null, null, null, null);
        $code = (string) $batch->vouchers()->first()->fresh()->code;
        app(VoucherService::class)->redeem($friend, $code);
    }

    public function test_every_customer_has_a_stable_unique_code(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $codeA = $this->codeOf($a);

        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{8}$/', $codeA);
        $this->assertSame($codeA, $this->codeOf($a->fresh()));
        $this->assertNotSame($codeA, $this->codeOf($b));
    }

    public function test_the_app_gets_the_code_the_reward_and_the_stats(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/referral')
            ->assertOk()
            ->assertJsonPath('reward_days', 30)
            ->assertJsonPath('enabled', true)
            ->assertJsonPath('can_apply', true)
            ->assertJsonPath('invited', 0)
            ->assertJsonPath('earned_days', 0)
            ->assertJsonPath('code', ReferralService::format($this->codeOf($user)));
    }

    private function friendJoins(User $inviter): User
    {
        $friend = User::factory()->create();
        $this->actingAs($friend, 'sanctum')->postJson('/api/v1/referral/apply', ['code' => ReferralService::format($this->codeOf($inviter))])
            ->assertOk()->assertJsonPath('referred', true)->assertJsonPath('can_apply', false);

        return $friend;
    }

    public function test_the_inviter_gets_one_month_of_plus_when_the_second_friend_joins(): void
    {
        $inviter = User::factory()->create();

        $this->friendJoins($inviter);
        $this->assertSame('pending', Referral::firstOrFail()->status);
        $this->assertSame('basic', $this->planOf($inviter));
        $this->assertSame(0, $inviter->notifications()->count());

        $this->friendJoins($inviter);

        $this->assertSame(2, Referral::where('status', 'rewarded')->count());
        $this->assertSame(30, (int) Referral::where('referrer_id', $inviter->id)->sum('reward_days'));
        $this->assertSame('plus', $this->planOf($inviter));
        $this->assertEqualsWithDelta(30, now()->diffInDays($inviter->subscriptions()->first()->current_period_ends_at), 0.1);

        $this->actingAs($inviter, 'sanctum')->getJson('/api/v1/referral')
            ->assertJsonPath('invited', 2)->assertJsonPath('rewarded', 2)->assertJsonPath('earned_days', 30);

        $this->assertSame(1, $inviter->notifications()->count());
    }

    public function test_the_reward_is_given_once_per_account_even_if_the_limit_is_raised(): void
    {
        SystemSetting::set('referral_max_invites', 10);
        Cache::flush();
        $inviter = User::factory()->create();

        foreach (range(1, 4) as $i) {
            $this->friendJoins($inviter);
        }

        $this->assertSame(1, $inviter->notifications()->count());
        $this->assertEqualsWithDelta(30, now()->diffInDays($inviter->subscriptions()->first()->current_period_ends_at), 0.1);
    }

    public function test_a_friend_paying_or_starting_a_trial_changes_nothing_for_the_inviter(): void
    {
        $inviter = User::factory()->create();
        $friend = $this->friendJoins($inviter);

        $this->friendPays($friend);
        app(SubscriptionManager::class)->startTrial(User::factory()->create());

        $this->assertSame('basic', $this->planOf($inviter));
        $this->assertSame(0, $inviter->notifications()->count());
    }

    public function test_a_code_cannot_be_used_on_yourself_twice_or_after_paying(): void
    {
        $inviter = User::factory()->create();
        $other = User::factory()->create();
        $friend = User::factory()->create();

        $this->actingAs($inviter, 'sanctum')->postJson('/api/v1/referral/apply', ['code' => $this->codeOf($inviter)])
            ->assertStatus(422)->assertJsonPath('error.code', 'REFERRAL_SELF');

        $this->actingAs($friend, 'sanctum')->postJson('/api/v1/referral/apply', ['code' => $this->codeOf($inviter)])->assertOk();
        $this->actingAs($friend, 'sanctum')->postJson('/api/v1/referral/apply', ['code' => $this->codeOf($other)])
            ->assertStatus(409)->assertJsonPath('error.code', 'REFERRAL_ALREADY');

        // Someone who already pays is not a new customer.
        $payer = User::factory()->create();
        app(SubscriptionManager::class)->grant($payer, $this->plus(), Admin::factory()->create()->id, 365);
        $this->actingAs($payer, 'sanctum')->postJson('/api/v1/referral/apply', ['code' => $this->codeOf($inviter)])
            ->assertStatus(409)->assertJsonPath('error.code', 'REFERRAL_NOT_NEW');
    }

    public function test_a_wrong_code_and_a_mutual_invite_are_refused(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $this->actingAs($b, 'sanctum')->postJson('/api/v1/referral/apply', ['code' => 'ZZZZ-ZZZZ'])
            ->assertStatus(422)->assertJsonPath('error.code', 'REFERRAL_INVALID');

        // A invited B, so A may not use B's code (that would let two accounts feed each other).
        $this->actingAs($b, 'sanctum')->postJson('/api/v1/referral/apply', ['code' => $this->codeOf($a)])->assertOk();
        $this->actingAs($a, 'sanctum')->postJson('/api/v1/referral/apply', ['code' => $this->codeOf($b)])
            ->assertStatus(422)->assertJsonPath('error.code', 'REFERRAL_INVALID');
    }

    public function test_an_account_can_invite_only_two_friends(): void
    {
        $inviter = User::factory()->create();
        $code = $this->codeOf($inviter);

        foreach ([1, 2] as $i) {
            $friend = User::factory()->create();
            $this->actingAs($friend, 'sanctum')->postJson('/api/v1/referral/apply', ['code' => $code])->assertOk();
        }

        $third = User::factory()->create();
        $this->actingAs($third, 'sanctum')->postJson('/api/v1/referral/apply', ['code' => $code])
            ->assertStatus(422)->assertJsonPath('error.code', 'REFERRAL_FULL');
        $this->assertSame(2, Referral::where('referrer_id', $inviter->id)->count());

        // The inviter sees how many are left; the third friend is free to use another code.
        $this->actingAs($inviter, 'sanctum')->getJson('/api/v1/referral')
            ->assertJsonPath('max_invites', 2)->assertJsonPath('remaining_invites', 0);
        $other = User::factory()->create();
        $this->actingAs($third, 'sanctum')->postJson('/api/v1/referral/apply', ['code' => $this->codeOf($other)])->assertOk();
    }

    public function test_the_programme_can_be_switched_off(): void
    {
        SystemSetting::set('referral_reward_days', 0);
        Cache::flush();
        $inviter = User::factory()->create();
        $friend = User::factory()->create();

        $this->actingAs($friend, 'sanctum')->getJson('/api/v1/referral')->assertJsonPath('enabled', false)->assertJsonPath('can_apply', false);
        $this->actingAs($friend, 'sanctum')->postJson('/api/v1/referral/apply', ['code' => $this->codeOf($inviter)])
            ->assertStatus(422)->assertJsonPath('error.code', 'REFERRAL_UNAVAILABLE');
    }

    public function test_the_dashboard_lists_the_invitations_and_exports_them(): void
    {
        $admin = Admin::factory()->create();
        $admin->roles()->sync([\App\Models\Role::where('slug', 'super_admin')->value('id')]);
        $inviter = User::factory()->create(['name' => 'الداعي']);
        $friend = User::factory()->create(['name' => 'الصديق']);
        $this->actingAs($friend, 'sanctum')->postJson('/api/v1/referral/apply', ['code' => $this->codeOf($inviter)])->assertOk();
        $second = User::factory()->create(['name' => 'الثاني']);
        $this->actingAs($second, 'sanctum')->postJson('/api/v1/referral/apply', ['code' => $this->codeOf($inviter)])->assertOk();

        $this->actingAs($admin, 'admin')->get('/admin/referrals')
            ->assertOk()->assertSee('الداعي')->assertSee('الصديق')->assertSee('تمت المكافأة');

        $this->actingAs($admin, 'admin')->get('/admin/referrals?export=xlsx')->assertOk();
    }
}
