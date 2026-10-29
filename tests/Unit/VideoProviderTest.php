<?php

namespace Tests\Unit;

use App\Domain\Video\DatabaseVideoProvider;
use App\Domain\Video\VideoMode;
use App\Domain\Video\VideoProviderInterface;
use App\Models\Provider;
use App\Models\Title;
use App\Models\VideoSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VideoProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_uses_mode_priority_before_source_priority(): void
    {
        $title = Title::create(['type' => 'movie', 'slug' => 'film', 'name' => 'Фильм', 'is_published' => true]);
        $provider = Provider::create(['name' => 'Demo', 'slug' => 'demo', 'is_active' => true]);
        VideoSource::create([
            'title_id' => $title->id, 'provider_id' => $provider->id, 'mode' => VideoMode::Trailer,
            'url' => 'https://example.com/trailer', 'priority' => 1, 'is_active' => true,
        ]);
        VideoSource::create([
            'title_id' => $title->id, 'provider_id' => $provider->id, 'mode' => VideoMode::Direct,
            'url' => 'https://example.com/video.mp4', 'legal_basis' => 'Public test media',
            'priority' => 100, 'countries' => ['KG'], 'is_active' => true,
        ]);

        $option = app(DatabaseVideoProvider::class)->getPlaybackOption($title, 'KG');

        $this->assertSame(VideoMode::Direct, $option->mode);
        $this->assertSame(VideoMode::Direct, app(VideoProviderInterface::class)->getPlaybackOption($title, 'KG')->mode);
    }

    public function test_direct_source_requires_https_and_legal_basis(): void
    {
        $this->expectException(ValidationException::class);
        $title = Title::create(['type' => 'movie', 'slug' => 'film', 'name' => 'Фильм']);
        $provider = Provider::create(['name' => 'Demo', 'slug' => 'demo']);

        VideoSource::create([
            'title_id' => $title->id,
            'provider_id' => $provider->id,
            'mode' => VideoMode::Direct,
            'url' => 'https://example.com/video.mp4',
        ]);
    }
}
