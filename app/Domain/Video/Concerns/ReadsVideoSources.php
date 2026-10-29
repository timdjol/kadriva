<?php

namespace App\Domain\Video\Concerns;

use App\Domain\Video\VideoMode;
use App\Domain\Video\VideoOption;
use App\Models\Title;
use App\Models\VideoSource;
use Illuminate\Support\Collection;

trait ReadsVideoSources
{
    /**
     * @param  list<VideoMode>  $modes
     * @return Collection<int, VideoSource>
     */
    protected function sourcesFor(Title $content, string $country, ?int $episodeId, array $modes): Collection
    {
        return VideoSource::query()
            ->with('provider')
            ->where('title_id', $content->id)
            ->where('episode_id', $episodeId)
            ->where('is_active', true)
            ->whereIn('mode', $modes)
            ->whereHas('provider', fn ($query) => $query->where('is_active', true))
            ->get()
            ->filter(fn (VideoSource $source) => empty($source->countries)
                || in_array(strtoupper($country), $source->countries, true))
            ->filter(fn (VideoSource $source) => ! in_array($source->mode, [VideoMode::Direct, VideoMode::Embed], true)
                || filled($source->legal_basis))
            ->sortBy(fn (VideoSource $source) => sprintf('%02d-%05d', $source->mode->rank(), $source->priority))
            ->values();
    }

    protected function optionFrom(VideoSource $source): VideoOption
    {
        return new VideoOption(
            $source->mode,
            $source->url,
            $source->provider?->name,
            $source->legal_basis,
        );
    }
}
