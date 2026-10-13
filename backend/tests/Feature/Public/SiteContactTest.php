<?php

namespace Tests\Feature\Public;

use App\Models\Admin;
use App\Models\Role;
use App\Models\SiteMessage;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * "Contact us" on the public site: the contact details, the form, and what happens
 * to a message (always saved, emailed on top, listed in the dashboard).
 */
class SiteContactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();
        $this->withoutVite();
    }

    public function test_the_landing_page_shows_the_email_and_both_phone_numbers_in_every_language(): void
    {
        foreach (['ar', 'en', 'ckb'] as $lang) {
            $this->get("/?lang={$lang}")->assertOk()
                ->assertSee('info@qreach.online')
                ->assertSee('+964 775 001 0277')
                ->assertSee('+964 785 001 0277')
                ->assertSee('href="mailto:info@qreach.online"', false)
                ->assertSee('href="tel:+9647750010277"', false);
        }

        $this->get('/?lang=ar')->assertSee('تواصل معنا');
        $this->get('/?lang=en')->assertSee('Send message');
        $this->get('/?lang=ckb')->assertSee('ناردنی پەیام');
    }

    public function test_a_message_is_saved_and_emailed_to_support(): void
    {
        Mail::fake();

        $this->post('/contact?lang=en', [
            'name' => 'Sara', 'email' => 'sara@example.com', 'message' => 'I would like 20 tags for my taxi fleet.',
        ])->assertRedirect(url('/').'?lang=en#contact')->assertSessionHas('contact_sent');

        $message = SiteMessage::firstOrFail();
        $this->assertSame('Sara', $message->name);
        $this->assertSame('sara@example.com', $message->email);
        $this->assertSame('en', $message->locale);
        $this->assertSame('new', $message->status);
    }

    public function test_the_visitor_sees_the_confirmation_after_sending(): void
    {
        $this->followingRedirects()->post('/contact?lang=en', [
            'name' => 'Ali', 'phone' => '0770 123 4567', 'message' => 'Please call me about stickers.',
        ])->assertOk()->assertSee('We received your message');
    }

    public function test_a_failing_mailer_never_loses_the_message_or_the_visitors_request(): void
    {
        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('smtp down'));

        $this->post('/contact', ['name' => 'Omar', 'email' => 'omar@example.com', 'message' => 'Hello there, a question.'])
            ->assertRedirect()->assertSessionHas('contact_sent');

        $this->assertSame(1, SiteMessage::count());
        $this->assertNull(SiteMessage::first()->emailed_at);
    }

    public function test_the_form_needs_a_name_a_way_to_reply_and_a_message(): void
    {
        $this->post('/contact?lang=en', ['name' => '', 'message' => ''])
            ->assertSessionHasErrors(['name', 'email', 'phone', 'message'], null, 'contact');

        $this->post('/contact?lang=en', ['name' => 'Sara', 'email' => 'not-an-email', 'message' => 'A real question here.'])
            ->assertSessionHasErrors(['email'], null, 'contact');

        $this->assertSame(0, SiteMessage::count());
    }

    public function test_errors_come_back_in_the_visitors_language(): void
    {
        $this->followingRedirects()->post('/contact?lang=ckb', ['name' => '', 'message' => 'سڵاو، پرسیارێکم هەیە'])
            ->assertOk()->assertSee('تکایە ناوەکەت بنووسە.');
    }

    public function test_a_bot_filling_the_hidden_field_is_ignored_silently(): void
    {
        $this->post('/contact', [
            'name' => 'Bot', 'email' => 'bot@example.com', 'message' => 'Buy cheap pills now', 'company_website' => 'http://spam.example',
        ])->assertRedirect()->assertSessionHas('contact_sent');

        $this->assertSame(0, SiteMessage::count());
    }

    public function test_markup_in_a_message_is_stripped(): void
    {
        $this->post('/contact', [
            'name' => '<b>Sara</b>', 'email' => 'sara@example.com', 'message' => 'Hi <script>alert(1)</script> there, call me.',
        ])->assertRedirect();

        $message = SiteMessage::firstOrFail();
        $this->assertStringNotContainsString('<script>', $message->message);
        $this->assertSame('Sara', $message->name);
    }

    public function test_the_form_is_rate_limited_per_address(): void
    {
        foreach (range(1, 3) as $i) {
            $this->post('/contact', ['name' => "N{$i}", 'email' => 'a@example.com', 'message' => "Message number {$i} here"])->assertRedirect();
        }

        $this->post('/contact', ['name' => 'N4', 'email' => 'a@example.com', 'message' => 'Message number 4 here'])->assertStatus(429);
        $this->assertSame(3, SiteMessage::count());
    }

    public function test_staff_see_the_messages_and_can_mark_them_read(): void
    {
        $message = SiteMessage::create(['name' => 'Layla', 'email' => 'layla@example.com', 'message' => 'أريد 50 ملصقًا لأسطول شركتي', 'locale' => 'ar']);
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);

        $this->actingAs($admin, 'admin')->get('/admin/site-messages')
            ->assertOk()->assertSee('Layla')->assertSee('أريد 50 ملصقًا لأسطول شركتي')->assertSee('جديدة');

        $this->actingAs($admin, 'admin')->get('/admin/site-messages?export=xlsx')->assertOk();

        $this->actingAs($admin, 'admin')->post('/admin/site-messages/'.$message->id.'/read')->assertRedirect();
        $this->assertSame('read', $message->fresh()->status);
    }
}
