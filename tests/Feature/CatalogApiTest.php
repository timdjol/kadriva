<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_filters_and_search_return_published_titles(): void
    {
        $genre = Genre::create(['name' => 'Драма', 'slug' => 'drama']);
        $title = Title::create([
            'type' => 'movie',
            'slug' => 'test-film',
            'name' => 'Тестовый фильм',
            'release_year' => 2026,
            'rating' => 8.2,
            'popularity' => 50,
            'is_published' => true,
        ]);
        $title->genres()->attach($genre);
        Title::create(['type' => 'series', 'slug' => 'hidden', 'name' => 'Скрытый', 'is_published' => false]);

        $this->getJson("/api/v1/titles?type=movie&genre={$genre->id}&year=2026&rating=8")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Тестовый фильм');

        $title->credits()->create(['name' => 'Вера Алентова', 'position' => 0]);

        $this->getJson('/api/v1/search?q='.urlencode('Тестовый'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $title->id);

        $this->getJson('/api/v1/search?q='.urlencode('Алентова').'&limit=8')
            ->assertOk()
            ->assertJsonPath('data.0.id', $title->id);

        $this->getJson("/api/v1/titles/{$title->id}")
            ->assertOk()
            ->assertJsonPath('data.slug', 'test-film')
            ->assertJsonPath('data.rating_source', 'TMDB');
    }

    public function test_home_returns_catalog_sections_and_country_detection(): void
    {
        Title::create([
            'type' => 'movie', 'slug' => 'popular', 'name' => 'Популярный', 'popularity' => 80, 'is_published' => true,
        ]);

        $this->getJson('/api/v1/home')
            ->assertOk()
            ->assertJsonPath('data.hero.name', 'Популярный')
            ->assertJsonStructure(['data' => ['sections', 'featured', 'new_movies']]);

        $this->withHeaders(['CF-IPCountry' => 'KZ'])->getJson('/api/v1/countries/detect')
            ->assertOk()
            ->assertJsonPath('data.code', 'KZ')
            ->assertJsonPath('data.source', 'ip');
    }

    public function test_validation_and_not_found_use_envelope(): void
    {
        $this->getJson('/api/v1/search?q=x')
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');

        $this->getJson('/api/v1/titles/999')
            ->assertNotFound()
            ->assertJsonPath('errors.0.code', 'NOT_FOUND');
    }
}
