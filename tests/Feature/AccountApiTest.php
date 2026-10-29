<?php

namespace Tests\Feature;

use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_manages_favorites_progress_and_settings(): void
    {
        $user = User::factory()->create();
        $title = Title::create([
            'type' => 'movie', 'slug' => 'film', 'name' => 'Фильм', 'is_published' => true,
        ]);
        $this->actingAs($user);

        $this->postJson('/api/v1/me/favorites', ['title_id' => $title->id])->assertCreated();
        $this->getJson('/api/v1/me/favorites')->assertOk()->assertJsonPath('data.0.id', $title->id);
        $this->deleteJson('/api/v1/me/favorites?title_id='.$title->id)->assertOk();
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'title_id' => $title->id]);

        $payload = ['position_seconds' => 120, 'duration_seconds' => 300];
        $this->putJson("/api/v1/me/progress/{$title->id}", $payload)
            ->assertOk()->assertJsonPath('data.position_seconds', 120);
        $this->getJson("/api/v1/me/progress/{$title->id}")
            ->assertOk()->assertJsonPath('data.duration_seconds', 300);

        $this->putJson('/api/v1/me/settings', ['country_code' => 'KZ'])
            ->assertOk()->assertJsonPath('data.country_code', 'KZ');

        $completed = $this->putJson("/api/v1/me/progress/{$title->id}", ['position_seconds' => 290, 'duration_seconds' => 300]);
        $completed->assertOk();
        $this->assertNotNull($completed->json('data.completed_at'));
        $this->getJson('/api/v1/me/history')->assertOk()->assertJsonPath('data.0.title.id', $title->id);
    }

    public function test_account_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/me/favorites')
            ->assertUnauthorized()
            ->assertJsonPath('errors.0.code', 'UNAUTHENTICATED');
    }
}
