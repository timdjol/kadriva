<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_login_and_logout_with_json_envelope(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Иван',
            'email' => 'ivan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'ivan@example.com')
            ->assertJsonPath('errors', []);
        $this->assertAuthenticated();

        $this->postJson('/api/v1/auth/logout')->assertOk();
        $this->assertGuest();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'ivan@example.com',
            'password' => 'password123',
        ])->assertOk()->assertJsonPath('data.role', 'user');
    }

    public function test_user_can_request_password_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('errors', []);
    }

    public function test_regular_user_cannot_access_filament_panel(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->assertFalse($user->canAccessPanel(filament()->getPanel('admin')));
    }
}
