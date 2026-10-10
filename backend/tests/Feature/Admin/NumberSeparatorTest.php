<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\TagBatch;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NumberSeparatorTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);

        return $admin;
    }

    public function test_the_limit_can_be_raised_to_ten_thousand_and_above_with_separators_typed(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->put('/admin/settings', ['otp_code_ttl_minutes' => '5', 'otp_resend_cooldown_seconds' => '60', 'qr_batch_max_quantity' => '10,000'])
            ->assertSessionHasNoErrors();
        $this->assertSame(10000, (int) SystemSetting::get('qr_batch_max_quantity', 0));

        $this->put('/admin/settings', ['otp_code_ttl_minutes' => '5', 'otp_resend_cooldown_seconds' => '60', 'qr_batch_max_quantity' => '20,001'])
            ->assertSessionHasErrors('qr_batch_max_quantity');
    }

    public function test_a_batch_quantity_typed_with_a_comma_or_arabic_digits_is_accepted(): void
    {
        $this->actingAs($this->admin(), 'admin');
        SystemSetting::set('qr_batch_max_quantity', 5000, null);

        $this->post('/admin/batches', ['quantity' => '1,200', 'tag_type' => 'qr'])->assertSessionHasNoErrors();
        $this->assertSame(1200, TagBatch::latest('id')->first()->quantity);

        $this->post('/admin/batches', ['quantity' => '٢٥٠', 'tag_type' => 'nfc'])->assertSessionHasNoErrors();
        $this->assertSame(250, TagBatch::latest('id')->first()->quantity);

        $this->post('/admin/batches', ['quantity' => '5,001', 'tag_type' => 'qr'])->assertSessionHasErrors('quantity');
    }

    public function test_the_forms_show_the_thousands_formatting_hook(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->get('/admin/batches/create')->assertOk()->assertSee('data-thousands', false)->assertSee('1,000');
        $this->get('/admin/settings')->assertOk()->assertSee('data-thousands', false);
    }

    public function test_the_quantity_box_carries_its_limit_and_bilingual_messages_for_live_validation(): void
    {
        $this->actingAs($this->admin(), 'admin');
        SystemSetting::set('qr_batch_max_quantity', 1000, null);

        $this->get('/admin/batches/create')->assertOk()
            ->assertSee('data-max="1000"', false)
            ->assertSee('الكمية يجب ألا تتجاوز 1,000 رمز في الدفعة الواحدة.')
            ->assertSee('Quantity must not exceed 1,000 codes per batch.');
    }

    public function test_the_server_refuses_an_oversize_quantity_with_a_readable_arabic_message(): void
    {
        $this->actingAs($this->admin(), 'admin');
        SystemSetting::set('qr_batch_max_quantity', 1000, null);

        $this->from('/admin/batches/create')->post('/admin/batches', ['quantity' => '1,001'])
            ->assertSessionHasErrors(['quantity' => 'الكمية يجب ألا تتجاوز 1,000 رمز في الدفعة الواحدة.']);

        $this->assertSame(0, TagBatch::count());
    }

    public function test_the_settings_inputs_carry_their_ranges(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->get('/admin/settings')->assertOk()
            ->assertSee('data-max="20000"', false)->assertSee('القيمة يجب ألا تتجاوز 20,000.');
    }
}
