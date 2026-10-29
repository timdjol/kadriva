<?php

namespace Tests\Unit;

use App\Services\Tmdb\TmdbImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TmdbImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_importer_stores_localized_title_credits_and_trailer(): void
    {
        config([
            'services.tmdb.url' => 'https://api.themoviedb.org/3',
            'services.tmdb.token' => 'test-token',
        ]);

        Http::fake([
            '*/movie/21028/credits*' => Http::response([
                'cast' => [['name' => 'Вера Алентова', 'character' => 'Катерина']],
            ]),
            '*/movie/21028/videos*' => Http::response([
                'results' => [['site' => 'YouTube', 'type' => 'Trailer', 'key' => 'abc123']],
            ]),
            '*/movie/21028*' => Http::response([
                'id' => 21028,
                'title' => 'Москва слезам не верит',
                'original_title' => 'Москва слезам не верит',
                'overview' => 'История трёх подруг.',
                'release_date' => '1980-02-11',
                'vote_average' => 8.4,
                'popularity' => 40,
                'runtime' => 150,
                'poster_path' => '/poster.jpg',
                'genres' => [['id' => 18, 'name' => 'Драма']],
            ]),
        ]);

        $title = app(TmdbImporter::class)->import(21028, 'movie', true);

        $this->assertSame('Москва слезам не верит', $title->name);
        $this->assertTrue($title->is_published);
        $this->assertSame('Вера Алентова', $title->credits->first()->name);
        $this->assertSame('Драма', $title->genres->first()->name);
        $this->assertDatabaseHas('video_sources', ['title_id' => $title->id, 'mode' => 'TRAILER']);
    }
}
