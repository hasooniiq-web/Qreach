<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds one bootstrap Super Admin with a random generated password (never a
 * hardcoded default — section 27) and forces a password change + TOTP setup
 * on first login.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        if (Admin::where('email', 'admin@qreach.app')->exists()) {
            return;
        }

        $password = Str::password(20);

        $admin = Admin::create([
            'name' => 'مسؤول النظام',
            'email' => 'admin@qreach.app',
            'password' => $password,
            'status' => 'active',
            'must_change_password' => true,
        ]);

        $admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);

        $this->command?->warn("Bootstrap admin created: admin@qreach.app / {$password} (must change on first login)");
    }
}
