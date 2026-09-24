<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Model `creating` events are relied on for uuid generation (User, Admin,
 * Vehicle, Tag, ...), so this seeder deliberately does NOT use
 * WithoutModelEvents.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);
    }
}
