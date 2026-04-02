<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_see_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('StudyForge', false);
    }

    public function test_authenticated_user_is_redirected_to_dashboard_from_landing_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
