<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\Title;
use App\Models\WatchProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function favorites(Request $request): JsonResponse
    {
        return $this->success($request->user()->favorites()->with('genres')->latest('favorites.created_at')->get());
    }

    public function addFavorite(Request $request): JsonResponse
    {
        $data = $request->validate(['title_id' => ['required', 'integer', 'exists:titles,id']]);
        $request->user()->favorites()->syncWithoutDetaching([$data['title_id']]);

        return $this->success(['title_id' => $data['title_id']], [], 201);
    }

    public function removeFavorite(Request $request): JsonResponse
    {
        $data = $request->validate(['title_id' => ['required', 'integer', 'exists:titles,id']]);
        $request->user()->favorites()->detach($data['title_id']);

        return $this->success(['title_id' => $data['title_id']]);
    }

    public function progress(Request $request, Title $title): JsonResponse
    {
        $data = $request->validate(['episode_id' => ['sometimes', 'integer', 'exists:episodes,id']]);

        return $this->success($this->progressQuery($request, $title, $data['episode_id'] ?? null)->first());
    }

    public function saveProgress(Request $request, Title $title): JsonResponse
    {
        $data = $request->validate([
            'episode_id' => ['nullable', 'integer', 'exists:episodes,id'],
            'position_seconds' => ['required', 'integer', 'min:0'],
            'duration_seconds' => ['required', 'integer', 'min:1'],
        ]);
        if ($data['position_seconds'] > $data['duration_seconds']) {
            return $this->error('INVALID_PROGRESS', 'Позиция не может превышать длительность.', 422, [
                'position_seconds' => ['Position exceeds duration.'],
            ]);
        }
        $episodeId = $data['episode_id'] ?? null;
        if ($episodeId && ! Episode::whereKey($episodeId)->whereHas('season', fn ($q) => $q->where('title_id', $title->id))->exists()) {
            return $this->error('INVALID_EPISODE', 'Эпизод не принадлежит этому тайтлу.', 422);
        }

        $progress = $this->progressQuery($request, $title, $episodeId)->first() ?? new WatchProgress([
            'user_id' => $request->user()->id,
            'title_id' => $title->id,
            'episode_id' => $episodeId,
        ]);
        $progress->fill($data + [
            'completed_at' => $data['position_seconds'] / $data['duration_seconds'] >= 0.9 ? now() : null,
            'last_watched_at' => now(),
        ])->save();

        return $this->success($progress->fresh());
    }

    public function settings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'country_code' => ['required', 'string', 'size:2', 'in:'.implode(',', array_keys(config('cinema.countries')))],
        ]);
        $request->user()->update($data);

        return $this->success($request->user()->only(['id', 'name', 'email', 'role', 'country_code']));
    }

    public function history(Request $request): JsonResponse
    {
        $items = $request->user()->watchProgress()
            ->with(['title.genres', 'episode'])
            ->latest('last_watched_at')
            ->limit(50)
            ->get()
            ->map(fn ($progress) => [
                'position_seconds' => $progress->position_seconds,
                'duration_seconds' => $progress->duration_seconds,
                'progress_percent' => round($progress->position_seconds / max(1, $progress->duration_seconds) * 100),
                'completed_at' => $progress->completed_at,
                'last_watched_at' => $progress->last_watched_at,
                'episode' => $progress->episode,
                'title' => $progress->title,
            ]);

        return $this->success($items);
    }

    private function progressQuery(Request $request, Title $title, ?int $episodeId)
    {
        return WatchProgress::query()
            ->where('user_id', $request->user()->id)
            ->where('title_id', $title->id)
            ->when($episodeId, fn ($query) => $query->where('episode_id', $episodeId), fn ($query) => $query->whereNull('episode_id'));
    }
}
