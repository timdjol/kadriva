<?php

namespace App\Models;

use App\Domain\Video\VideoMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class VideoSource extends Model
{
    protected $fillable = [
        'title_id', 'episode_id', 'provider_id', 'mode', 'url', 'legal_basis',
        'countries', 'audio_languages', 'subtitle_languages', 'last_checked_at',
        'priority', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'mode' => VideoMode::class,
            'countries' => 'array',
            'audio_languages' => 'array',
            'subtitle_languages' => 'array',
            'last_checked_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (VideoSource $source): void {
            if (! filter_var($source->url, FILTER_VALIDATE_URL)
                || ! in_array(parse_url($source->url, PHP_URL_SCHEME), ['https'], true)) {
                throw ValidationException::withMessages(['url' => 'Источник должен использовать корректный HTTPS URL.']);
            }

            $mode = $source->mode instanceof VideoMode ? $source->mode : VideoMode::from($source->mode);
            if (in_array($mode, [VideoMode::Direct, VideoMode::Embed], true)
                && blank($source->legal_basis)) {
                throw ValidationException::withMessages(['legal_basis' => 'Для DIRECT и EMBED требуется правовое основание.']);
            }

            if ($mode === VideoMode::Embed
                && ! in_array(parse_url($source->url, PHP_URL_HOST), config('video.embed_hosts'), true)) {
                throw ValidationException::withMessages(['url' => 'Домен EMBED отсутствует в VIDEO_EMBED_HOSTS.']);
            }
        });
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
