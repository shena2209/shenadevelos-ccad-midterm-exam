<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\UsesSchoolDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;
    use UsesSchoolDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureSchoolDatabase();
    }

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_guests_are_redirected_to_login_from_the_homepage()
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_authenticated_users_are_redirected_to_the_dashboard_from_the_homepage()
    {
        $this->actingAs(User::factory()->create());

        $this->get('/')->assertRedirect('/dashboard');
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $this->actingAs($user = User::factory()->create());

        $this->get('/dashboard')->assertOk();
    }
}
