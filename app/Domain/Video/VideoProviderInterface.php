<?php

namespace App\Domain\Video;

use App\Models\Title;

interface VideoProviderInterface
{
    public function supports(Title $content, string $country): bool;

    /**
     * @return list<VideoOption>
     */
    public function getAvailability(Title $content, string $country, ?int $episodeId = null): array;

    public function getPlaybackOption(Title $content, string $country, ?int $episodeId = null): VideoOption;
}
