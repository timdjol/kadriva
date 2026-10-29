<?php

namespace App\Services\Tmdb;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class TmdbClient
{
    public function title(int $tmdbId, string $type = 'movie'): array
    {
        return $this->localized("{$this->pathType($type)}/{$tmdbId}", ['title', 'name', 'overview', 'poster_path', 'backdrop_path']);
    }

    public function credits(int $tmdbId, string $type = 'movie'): array
    {
        return $this->request()->get("{$this->pathType($type)}/{$tmdbId}/credits")->throw()->json();
    }

    public function videos(int $tmdbId, string $type = 'movie'): array
    {
        $payload = $this->localized("{$this->pathType($type)}/{$tmdbId}/videos");
        if (blank($payload['results'] ?? null)) {
            $payload = $this->request()->get("{$this->pathType($type)}/{$tmdbId}/videos", ['language' => 'en-US'])->throw()->json();
        }

        return $payload;
    }

    public function season(int $tmdbId, int $seasonNumber): array
    {
        return $this->localized("tv/{$tmdbId}/season/{$seasonNumber}", ['name', 'overview']);
    }

    public function imageUrl(?string $path, string $size = 'w500'): ?string
    {
        if (blank($path)) {
            return null;
        }

        return 'https://image.tmdb.org/t/p/'.$size.$path;
    }

    private function localized(string $path, array $fields = []): array
    {
        $russian = $this->request()->get($path, ['language' => 'ru-RU'])->throw()->json();
        $english = [];

        $needsFallback = collect($fields ?: ['name', 'title', 'overview'])
            ->contains(fn (string $field) => blank($russian[$field] ?? null));

        if ($needsFallback || blank($russian['results'] ?? true) && array_key_exists('results', $russian)) {
            $english = $this->request()->get($path, ['language' => 'en-US'])->throw()->json();
        }

        foreach ($fields as $field) {
            if (blank($russian[$field] ?? null) && filled($english[$field] ?? null)) {
                $russian[$field] = $english[$field];
            }
        }

        if (blank($russian['results'] ?? null) && filled($english['results'] ?? null)) {
            $russian['results'] = $english['results'];
        }

        return $russian;
    }

    private function pathType(string $type): string
    {
        $type = $type === 'series' ? 'tv' : $type;
        if (! in_array($type, ['movie', 'tv'], true)) {
            throw new RuntimeException('Unsupported TMDB title type.');
        }

        return $type;
    }

    private function request(): PendingRequest
    {
        $token = config('services.tmdb.token');
        $key = config('services.tmdb.key');

        if (blank($token) && blank($key)) {
            throw new RuntimeException('TMDB credentials are not configured.');
        }

        $base = rtrim((string) config('services.tmdb.url'), '/');
        if (! str_ends_with($base, '/3')) {
            $base .= '/3';
        }

        return Http::baseUrl($base)
            ->acceptJson()
            ->when($token, fn (PendingRequest $request) => $request->withToken($token))
            ->when(! $token && $key, fn (PendingRequest $request) => $request->withQueryParameters(['api_key' => $key]))
            ->timeout(10)
            ->retry(2, 200);
    }
}
