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
}
