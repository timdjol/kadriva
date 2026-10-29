<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Video\VideoProviderInterface;
use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Episode;
use App\Models\Genre;
use App\Models\Title;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CatalogController extends Controller
{
    public function home(Request $request): JsonResponse
    {
        $base = fn () => Title::published()->with('genres');
        $map = fn ($items) => collect($items)->map($this->titleData(...))->values();
        $byGenre = fn (string $slug) => $base()->whereHas('genres', fn ($query) => $query->where('slug', $slug))
            ->orderByDesc('popularity')->limit(12)->get();

        $continue = collect();
        if ($request->user()) {
            $continue = $request->user()->watchProgress()
                ->with(['title.genres', 'episode'])
                ->whereNull('completed_at')
                ->latest('last_watched_at')
                ->limit(12)
                ->get()
                ->filter(fn ($progress) => $progress->title)
                ->map(fn ($progress) => $this->titleData($progress->title) + [
                    'progress_percent' => round($progress->position_seconds / max(1, $progress->duration_seconds) * 100),
                    'episode_id' => $progress->episode_id,
                    'episode' => $progress->episode,
                ]);
        }

        $featured = $base()->orderByDesc('popularity')->limit(12)->get();
        $newMovies = $base()->where('type', 'movie')->orderByDesc('release_date')->limit(12)->get();
        $series = $base()->where('type', 'series')->orderByDesc('popularity')->limit(12)->get();
        $cartoons = $byGenre('multfilm');
        $anime = $byGenre('anime');
        $collections = Collection::query()->where('is_published', true)->orderBy('sort_order')
            ->with(['titles' => fn ($query) => $query->published()->with('genres')])
            ->get()
            ->map(fn (Collection $collection) => [
                'id' => $collection->id,
                'name' => $collection->name,
                'slug' => $collection->slug,
                'titles' => $collection->titles->map($this->titleData(...)),
            ]);

        $sections = collect([
            ['title' => 'Продолжить просмотр', 'items' => $continue],
            ['title' => 'Популярное', 'items' => $map($featured)],
            ['title' => 'Новинки', 'items' => $map($newMovies)],
            ['title' => 'Фильмы', 'items' => $map($newMovies)],
            ['title' => 'Сериалы', 'items' => $map($series)],
            ['title' => 'Мультфильмы', 'items' => $map($cartoons)],
            ['title' => 'Аниме', 'items' => $map($anime)],
            ...$collections->map(fn (array $collection) => ['title' => $collection['name'], 'items' => $collection['titles']]),
        ])->filter(fn (array $section) => collect($section['items'])->isNotEmpty())->values();

        $hero = $featured->first();

        return $this->success([
            'hero' => $hero ? $this->titleData($hero) + ['description' => $hero->description] : null,
            'featured' => $map($featured->take(6)),
            'new_movies' => $map($newMovies),
            'series' => $map($series),
            'cartoons' => $map($cartoons),
            'anime' => $map($anime),
            'collections' => $collections,
            'continue_watching' => $continue,
            'sections' => $sections,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'type' => ['sometimes', Rule::in(['movie', 'series'])],
            'genre' => ['sometimes', 'integer', 'exists:genres,id'],
            'year' => ['sometimes', 'integer', 'min:1888', 'max:2100'],
            'rating' => ['sometimes', 'numeric', 'between:0,10'],
            'sort' => ['sometimes', Rule::in(['popular', 'rating', 'release_date'])],
        ]);

        $sort = ['popular' => 'popularity', 'rating' => 'rating', 'release_date' => 'release_date'];
        $query = Title::published()->with('genres')
            ->when($validated['type'] ?? null, fn ($q, $value) => $q->where('type', $value))
            ->when($validated['genre'] ?? null, fn ($q, $value) => $q->whereHas('genres', fn ($genres) => $genres->whereKey($value)))
            ->when($validated['year'] ?? null, fn ($q, $value) => $q->where('release_year', $value))
            ->when(isset($validated['rating']), fn ($q) => $q->where('rating', '>=', $validated['rating']))
            ->orderByDesc($sort[$validated['sort'] ?? 'popular']);
        $page = $query->paginate(20);

        return $this->success(
            collect($page->items())->map($this->titleData(...)),
            ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()]
        );
    }

    public function show(Title $title): JsonResponse
    {
        abort_unless($title->is_published, 404);
        $title->load(['genres', 'credits']);

        return $this->success($this->titleData($title) + [
            'description' => $title->description,
            'overview' => $title->description,
            'original_title' => $title->original_name,
            'runtime_minutes' => $title->runtime_minutes,
            'backdrop_url' => $title->backdrop_url,
            'age_rating' => $title->age_rating,
            'rating_source' => 'TMDB',
            'actors' => $title->credits->map->only(['name', 'character']),
        ]);
    }

    public function seasons(Title $title): JsonResponse
    {
        abort_unless($title->is_published, 404);

        return $this->success($title->seasons()->with('episodes')->get());
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $escaped = addcslashes($validated['q'], '%_');
        $page = Title::published()->with('genres')
            ->where(fn ($query) => $query->where('name', 'like', "%{$escaped}%")
                ->orWhere('original_name', 'like', "%{$escaped}%"))
            ->orderByDesc('popularity')->paginate(20);

        return $this->success(
            collect($page->items())->map($this->titleData(...)),
            ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()]
        );
    }

    public function watchOptions(Request $request, Title $title, VideoProviderInterface $provider): JsonResponse
    {
        abort_unless($title->is_published, 404);
        $validated = $request->validate([
            'country' => ['sometimes', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'episode_id' => ['sometimes', 'integer', 'exists:episodes,id'],
        ]);
        $episodeId = $validated['episode_id'] ?? null;
        if ($episodeId && ! Episode::whereKey($episodeId)->whereHas('season', fn ($q) => $q->where('title_id', $title->id))->exists()) {
            return $this->error('INVALID_EPISODE', 'Эпизод не принадлежит этому тайтлу.', 422, ['episode_id' => ['Invalid episode.']]);
        }

        $country = $validated['country'] ?? $request->user()?->country_code ?? 'KG';
        $options = $provider->getAvailability($title, $country, $episodeId);
        $current = $options[0] ?? $provider->getPlaybackOption($title, $country, $episodeId);

        return $this->success([
            'current' => $current,
            'options' => $options ?: [$current],
        ]);
    }

    public function countries(): JsonResponse
    {
        return $this->success(collect(config('cinema.countries'))
            ->map(fn (string $name, string $code) => ['code' => $code, 'name' => $name])
            ->values());
    }

    public function detectCountry(Request $request): JsonResponse
    {
        $allowed = array_keys(config('cinema.countries'));
        $header = strtoupper((string) ($request->header('CF-IPCountry') ?: $request->header('X-Appengine-Country') ?: ''));
        $known = in_array($header, $allowed, true);

        return $this->success([
            'code' => $known ? $header : 'KG',
            'source' => $known ? 'ip' : 'default',
            'needs_confirmation' => true,
        ]);
    }

    public function genres(): JsonResponse
    {
        return $this->success(Genre::query()->orderBy('name')->get(['id', 'name', 'slug']));
    }

    private function titleData(Title $title): array
    {
        return [
            'id' => $title->id,
            'type' => $title->type,
            'slug' => $title->slug,
            'name' => $title->name,
            'original_name' => $title->original_name,
            'release_year' => $title->release_year,
            'rating' => (float) $title->rating,
            'poster_url' => $title->poster_url,
            'backdrop_url' => $title->backdrop_url,
            'country_code' => $title->country_code,
            'age_rating' => $title->age_rating,
            'rating_source' => 'TMDB',
            'genres' => $title->genres->map->only(['id', 'name', 'slug']),
        ];
    }
}
