<?php

namespace Tests\Feature;

use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_root_url_serves_the_public_landing_page(): void
    {
        $this->seed(SubscriptionPlanSeeder::class);
        $this->withoutVite();

        $this->get('/')->assertOk()->assertSee('Qreach SmartTag');
    }

    public function test_the_landing_page_still_renders_when_no_plans_exist(): void
    {
        $this->withoutVite();

        $this->get('/?lang=en')->assertOk()->assertSee('One tag on your car');
    }

    public function test_the_dashboard_stays_behind_its_own_login(): void
    {
        $this->get('/admin/login')->assertOk();
    }
}
