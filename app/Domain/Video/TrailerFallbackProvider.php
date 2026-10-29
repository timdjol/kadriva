<?php

namespace App\Domain\Video;

use App\Domain\Video\Concerns\ReadsVideoSources;
use App\Models\Title;

final class TrailerFallbackProvider implements VideoProviderInterface
{
    use ReadsVideoSources;

    public function supports(Title $content, string $country): bool
    {
        return $this->getAvailability($content, $country) !== [];
    }

    public function getAvailability(Title $content, string $country, ?int $episodeId = null): array
    {
        return $this->sourcesFor($content, $country, $episodeId, [VideoMode::Trailer])
            ->map($this->optionFrom(...))
            ->all();
    }

    public function getPlaybackOption(Title $content, string $country, ?int $episodeId = null): VideoOption
    {
        return $this->getAvailability($content, $country, $episodeId)[0] ?? new VideoOption(VideoMode::Unavailable);
    }
}
