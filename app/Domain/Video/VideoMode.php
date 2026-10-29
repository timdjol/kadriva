<?php

namespace App\Domain\Video;

enum VideoMode: string
{
    case Direct = 'DIRECT';
    case Embed = 'EMBED';
    case External = 'EXTERNAL';
    case Trailer = 'TRAILER';
    case Unavailable = 'UNAVAILABLE';

    public function rank(): int
    {
        return match ($this) {
            self::Direct, self::Embed => 1,
            self::External => 2,
            self::Trailer => 3,
            self::Unavailable => 4,
        };
    }
}
