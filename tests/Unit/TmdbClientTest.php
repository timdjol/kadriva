<?php

namespace Tests\Unit;

use App\Services\Tmdb\TmdbClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TmdbClientTest extends TestCase
{
    public function test_client_uses_russian_and_falls_back_to_english(): void
    {
        config([
            'services.tmdb.url' => 'https://api.themoviedb.org',
            'services.tmdb.token' => 'test-token',
        ]);
        Http::fake(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

            return Http::response(
                ($query['language'] ?? null) === 'ru-RU'
                    ? ['id' => 1, 'title' => 'Название', 'overview' => '']
                    : ['id' => 1, 'title' => 'English title', 'overview' => 'English overview'],
                200
            );
        });

        $result = app(TmdbClient::class)->title(1);

        $this->assertSame('Название', $result['title']);
        $this->assertSame('English overview', $result['overview']);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-token'));
    }
}
