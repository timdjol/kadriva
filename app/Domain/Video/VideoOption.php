<?php

namespace App\Domain\Video;

use JsonSerializable;

final readonly class VideoOption implements JsonSerializable
{
    public function __construct(
        public VideoMode $mode,
        public ?string $url = null,
        public ?string $provider = null,
        public ?string $legalBasis = null,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'mode' => $this->mode->value,
            'url' => $this->url,
            'provider' => $this->provider,
            'legal_basis' => $this->legalBasis,
        ];
    }
}
