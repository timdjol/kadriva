<?php

namespace App\Domain\Video;

use App\Models\Title;

final class CompositeVideoProvider implements VideoProviderInterface
{
    /**
     * @param  list<VideoProviderInterface>  $providers
     */
    public function __construct(private readonly array $providers) {}

    public function supports(Title $content, string $country): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($content, $country)) {
                return true;
            }
        }

        return false;
    }

    public function getAvailability(Title $content, string $country, ?int $episodeId = null): array
    {
        $options = [];
        foreach ($this->providers as $provider) {
            foreach ($provider->getAvailability($content, $country, $episodeId) as $option) {
                if ($option->mode !== VideoMode::Unavailable) {
                    $options[] = $option;
                }
            }
        }

        usort($options, fn (VideoOption $left, VideoOption $right) => $left->mode->rank() <=> $right->mode->rank());

        return array_values($options);
    }

    public function getPlaybackOption(Title $content, string $country, ?int $episodeId = null): VideoOption
    {
        return $this->getAvailability($content, $country, $episodeId)[0] ?? new VideoOption(VideoMode::Unavailable);
    }
}
