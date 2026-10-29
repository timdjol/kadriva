<?php

namespace App\Services\Tmdb;

use App\Domain\Video\VideoMode;
use App\Models\Genre;
use App\Models\Provider;
use App\Models\Title;
use App\Models\VideoSource;
use Illuminate\Support\Str;

final class TmdbImporter
{
    public function __construct(private readonly TmdbClient $client) {}

    public function import(int $tmdbId, string $type = 'movie', bool $publish = false): Title
    {
        $tmdbType = $type === 'series' ? 'tv' : 'movie';
        $payload = $this->client->title($tmdbId, $tmdbType);
        $localType = $tmdbType === 'tv' ? 'series' : 'movie';
        $name = $payload['title'] ?? $payload['name'] ?? 'Untitled';
        $release = $payload['release_date'] ?? $payload['first_air_date'] ?? null;

        $title = Title::updateOrCreate(
            ['tmdb_id' => $tmdbId, 'type' => $localType],
            [
                'slug' => $this->uniqueSlug(Str::slug($name) ?: 'title-'.$tmdbId, $tmdbId, $localType),
                'name' => $name,
                'original_name' => $payload['original_title'] ?? $payload['original_name'] ?? $name,
                'description' => $payload['overview'] ?? null,
                'release_date' => $release ?: null,
                'release_year' => $release ? (int) substr($release, 0, 4) : null,
                'rating' => $payload['vote_average'] ?? 0,
                'popularity' => (int) round($payload['popularity'] ?? 0),
                'runtime_minutes' => $payload['runtime'] ?? null,
                'poster_url' => $this->client->imageUrl($payload['poster_path'] ?? null),
                'backdrop_url' => $this->client->imageUrl($payload['backdrop_path'] ?? null, 'w1280'),
                'country_code' => strtoupper($payload['origin_country'][0] ?? $payload['production_countries'][0]['iso_3166_1'] ?? 'RU'),
                'age_rating' => $this->ageRating($payload),
                'status' => $payload['status'] ?? null,
                'source_updated_at' => now(),
                'imported_at' => now(),
                'is_published' => $publish,
            ]
        );

        $this->syncGenres($title, $payload['genres'] ?? []);
        $this->syncCredits($title, $this->client->credits($tmdbId, $tmdbType)['cast'] ?? []);
        $this->syncTrailer($title, $this->client->videos($tmdbId, $tmdbType)['results'] ?? []);

        if ($localType === 'series') {
            $this->syncSeasons($title, $payload['seasons'] ?? []);
        }

        return $title->fresh(['genres', 'credits', 'seasons.episodes']);
    }

    private function uniqueSlug(string $slug, int $tmdbId, string $type): string
    {
        $exists = Title::query()->where('slug', $slug)->where(fn ($query) => $query
            ->where('tmdb_id', '!=', $tmdbId)
            ->orWhere('type', '!=', $type))->exists();

        return $exists ? $slug.'-'.$tmdbId : $slug;
    }

    private function ageRating(array $payload): ?string
    {
        foreach ($payload['release_dates']['results'] ?? [] as $country) {
            foreach ($country['release_dates'] ?? [] as $item) {
                if (filled($item['certification'] ?? null)) {
                    return $item['certification'];
                }
            }
        }

        foreach ($payload['content_ratings']['results'] ?? [] as $item) {
            if (filled($item['rating'] ?? null)) {
                return $item['rating'];
            }
        }

        return null;
    }

    private function syncGenres(Title $title, array $genres): void
    {
        $ids = [];
        foreach ($genres as $genre) {
            if (blank($genre['id'] ?? null) || blank($genre['name'] ?? null)) {
                continue;
            }
            $model = Genre::updateOrCreate(
                ['tmdb_id' => $genre['id']],
                ['name' => $genre['name'], 'slug' => Str::slug($genre['name']) ?: 'genre-'.$genre['id']],
            );
            $ids[] = $model->id;
        }
        $title->genres()->sync($ids);
    }

    private function syncCredits(Title $title, array $cast): void
    {
        $title->credits()->delete();
        foreach (array_slice($cast, 0, 12) as $index => $person) {
            if (blank($person['name'] ?? null)) {
                continue;
            }
            $title->credits()->create([
                'name' => $person['name'],
                'character' => $person['character'] ?? null,
                'position' => $index,
            ]);
        }
    }

    private function syncTrailer(Title $title, array $videos): void
    {
        $trailer = collect($videos)->first(fn (array $video) => ($video['site'] ?? null) === 'YouTube'
            && in_array($video['type'] ?? null, ['Trailer', 'Teaser'], true)
            && filled($video['key'] ?? null));

        if (! $trailer) {
            return;
        }

        $provider = Provider::updateOrCreate(
            ['slug' => 'tmdb-trailer'],
            ['name' => 'TMDB Trailer', 'homepage_url' => 'https://www.themoviedb.org', 'integration_type' => 'trailer', 'is_active' => true],
        );

        VideoSource::updateOrCreate(
            ['title_id' => $title->id, 'episode_id' => null, 'provider_id' => $provider->id, 'mode' => VideoMode::Trailer],
            [
                'url' => 'https://www.youtube.com/watch?v='.$trailer['key'],
                'countries' => null,
                'priority' => 90,
                'is_active' => true,
                'last_checked_at' => now(),
            ],
        );
    }

    private function syncSeasons(Title $title, array $seasons): void
    {
        foreach ($seasons as $season) {
            $number = (int) ($season['season_number'] ?? 0);
            if ($number < 1) {
                continue;
            }
            $details = $this->client->season((int) $title->tmdb_id, $number);
            $model = $title->seasons()->updateOrCreate(
                ['number' => $number],
                [
                    'tmdb_id' => $details['id'] ?? $season['id'] ?? null,
                    'name' => $details['name'] ?? $season['name'] ?? 'Сезон '.$number,
                    'description' => $details['overview'] ?? $season['overview'] ?? null,
                    'poster_url' => $this->client->imageUrl($details['poster_path'] ?? $season['poster_path'] ?? null),
                    'air_date' => $details['air_date'] ?? $season['air_date'] ?? null,
                ]
            );
            foreach ($details['episodes'] ?? [] as $episode) {
                $episodeNumber = (int) ($episode['episode_number'] ?? 0);
                if ($episodeNumber < 1) {
                    continue;
                }
                $model->episodes()->updateOrCreate(
                    ['number' => $episodeNumber],
                    [
                        'tmdb_id' => $episode['id'] ?? null,
                        'name' => $episode['name'] ?? 'Серия '.$episodeNumber,
                        'description' => $episode['overview'] ?? null,
                        'runtime_minutes' => $episode['runtime'] ?? null,
                        'still_url' => $this->client->imageUrl($episode['still_path'] ?? null),
                        'air_date' => $episode['air_date'] ?? null,
                    ]
                );
            }
        }
    }
}
