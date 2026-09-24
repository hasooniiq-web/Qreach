<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_root_url_redirects_to_admin_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/admin/login');
    }
}
