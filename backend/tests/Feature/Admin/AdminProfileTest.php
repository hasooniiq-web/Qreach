<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The admin's own profile: name, photo, password, and the header account
 * menu that replaced the sidebar logout button. Persisted values are
 * asserted directly because mass-assignment failures in this codebase have
 * been silent (see docs/TESTING.md).
 */
class AdminProfileTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $attrs = []): Admin
    {
        return Admin::factory()->create($attrs + ['must_change_password' => false]);
    }

    public function test_header_menu_has_profile_and_logout_and_the_sidebar_has_no_logout(): void
    {
        $admin = $this->admin();

        $html = $this->actingAs($admin, 'admin')->get('/admin/profile')->assertOk()->getContent();

        $this->assertStringContainsString('الملف الشخصي', $html);
        // Exactly one logout form on the page (in the header menu), not one per sidebar.
        $this->assertSame(1, substr_count($html, 'action="'.route('admin.logout').'"'));
        $this->assertSame(1, preg_match('#<details data-menu.*?admin/logout.*?</details>#s', $html));
    }

    public function test_profile_requires_authentication(): void
    {
        $this->get('/admin/profile')->assertRedirect();
        $this->get('/admin/profile/avatar')->assertRedirect();
    }

    public function test_name_update_is_persisted_and_validated(): void
    {
        $admin = $this->admin(['name' => 'الاسم القديم']);

        $this->actingAs($admin, 'admin')->put('/admin/profile', ['name' => '  محمد علي  '])->assertRedirect();
        $this->assertSame('محمد علي', $admin->fresh()->name);

        $this->actingAs($admin, 'admin')->put('/admin/profile', ['name' => 'x'])->assertSessionHasErrors('name');
        $this->assertSame('محمد علي', $admin->fresh()->name);
    }

    public function test_updating_the_profile_cannot_touch_another_admin(): void
    {
        $me = $this->admin(['name' => 'أنا']);
        $other = $this->admin(['name' => 'الآخر']);

        $this->actingAs($me, 'admin')->put('/admin/profile', ['name' => 'اسم جديد', 'id' => $other->id, 'admin_id' => $other->id]);

        $this->assertSame('اسم جديد', $me->fresh()->name);
        $this->assertSame('الآخر', $other->fresh()->name);
    }

    public function test_avatar_upload_is_stored_privately_normalized_and_served(): void
    {
        Storage::fake('local');
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->post('/admin/profile/avatar', ['avatar' => UploadedFile::fake()->image('me.png', 800, 500)])
            ->assertRedirect();

        $admin->refresh();
        $this->assertSame("admin-avatars/{$admin->uuid}.jpg", $admin->avatar_path);
        Storage::disk('local')->assertExists($admin->avatar_path);

        // Re-encoded to a 256x256 JPEG regardless of what was uploaded.
        [$w, $h, $type] = getimagesizefromstring(Storage::disk('local')->get($admin->avatar_path));
        $this->assertSame([256, 256, IMAGETYPE_JPEG], [$w, $h, $type]);

        $this->actingAs($admin, 'admin')->get('/admin/profile/avatar')->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.avatar_updated']);
    }

    public function test_avatar_rejects_non_images_and_oversized_or_tiny_files(): void
    {
        Storage::fake('local');
        $admin = $this->admin();

        $fakeImage = UploadedFile::fake()->createWithContent('evil.png', '<?php echo "not an image";');
        $this->actingAs($admin, 'admin')->post('/admin/profile/avatar', ['avatar' => $fakeImage])->assertSessionHasErrors('avatar');

        $this->actingAs($admin, 'admin')->post('/admin/profile/avatar', ['avatar' => UploadedFile::fake()->image('big.jpg', 600, 600)->size(3000)])
            ->assertSessionHasErrors('avatar');

        $this->actingAs($admin, 'admin')->post('/admin/profile/avatar', ['avatar' => UploadedFile::fake()->image('tiny.png', 20, 20)])
            ->assertSessionHasErrors('avatar');

        $this->assertNull($admin->fresh()->avatar_path);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_avatar_can_be_removed(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->post('/admin/profile/avatar', ['avatar' => UploadedFile::fake()->image('me.jpg', 300, 300)]);
        $path = $admin->fresh()->avatar_path;

        $this->actingAs($admin, 'admin')->delete('/admin/profile/avatar')->assertRedirect();

        $this->assertNull($admin->fresh()->avatar_path);
        Storage::disk('local')->assertMissing($path);
        $this->actingAs($admin, 'admin')->get('/admin/profile/avatar')->assertNotFound();
    }

    public function test_password_change_requires_the_current_password_and_is_persisted(): void
    {
        $admin = $this->admin(['password' => Hash::make('OldPassword!2026')]);

        $this->actingAs($admin, 'admin')->put('/admin/profile/password', [
            'current_password' => 'wrong-password', 'password' => 'BrandNewPassw0rd!', 'password_confirmation' => 'BrandNewPassw0rd!',
        ])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('OldPassword!2026', $admin->fresh()->password));

        $this->actingAs($admin, 'admin')->put('/admin/profile/password', [
            'current_password' => 'OldPassword!2026', 'password' => 'BrandNewPassw0rd!', 'password_confirmation' => 'BrandNewPassw0rd!',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $fresh = $admin->fresh();
        $this->assertTrue(Hash::check('BrandNewPassw0rd!', $fresh->password));
        $this->assertFalse(Hash::check('OldPassword!2026', $fresh->password));
        $this->assertNotNull($fresh->password_changed_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.password_changed']);
    }

    public function test_password_change_rejects_short_unconfirmed_or_unchanged_passwords(): void
    {
        $admin = $this->admin(['password' => Hash::make('OldPassword!2026')]);
        $put = fn (array $data) => $this->actingAs($admin, 'admin')->put('/admin/profile/password', ['current_password' => 'OldPassword!2026'] + $data);

        $put(['password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');
        $put(['password' => 'BrandNewPassw0rd!', 'password_confirmation' => 'different'])->assertSessionHasErrors('password');
        $put(['password' => 'OldPassword!2026', 'password_confirmation' => 'OldPassword!2026'])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('OldPassword!2026', $admin->fresh()->password));
    }
}
