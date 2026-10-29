<?php

namespace Database\Seeders;

use App\Domain\Video\VideoMode;
use App\Models\Collection;
use App\Models\Genre;
use App\Models\Provider;
use App\Models\Title;
use App\Models\User;
use App\Models\VideoSource;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'Администратор', 'email' => 'admin@example.com', 'role' => 'admin'],
            ['name' => 'Редактор', 'email' => 'editor@example.com', 'role' => 'editor'],
            ['name' => 'Зритель', 'email' => 'user@example.com', 'role' => 'user'],
        ] as $account) {
            User::updateOrCreate(['email' => $account['email']], $account + [
                'password' => Hash::make('password'),
                'country_code' => 'KG',
            ]);
        }

        $genres = collect([
            ['name' => 'Драма', 'slug' => 'drama'],
            ['name' => 'Комедия', 'slug' => 'comedy'],
            ['name' => 'Фантастика', 'slug' => 'sci-fi'],
            ['name' => 'Мультфильм', 'slug' => 'multfilm'],
            ['name' => 'Аниме', 'slug' => 'anime'],
        ])->mapWithKeys(fn (array $genre) => [
            $genre['slug'] => Genre::updateOrCreate(['slug' => $genre['slug']], $genre),
        ]);

        $moscow = Title::updateOrCreate(['slug' => 'moskva-slezam-ne-verit'], [
            'tmdb_id' => 21028,
            'type' => 'movie',
            'name' => 'Москва слезам не верит',
            'original_name' => 'Москва слезам не верит',
            'description' => 'История трёх подруг, приехавших покорять Москву.',
            'release_year' => 1979,
            'release_date' => '1980-02-11',
            'rating' => 8.4,
            'popularity' => 900,
            'runtime_minutes' => 150,
            'country_code' => 'RU',
            'age_rating' => '12+',
            'is_published' => true,
        ]);
        $moscow->genres()->sync([$genres['drama']->id]);
        $moscow->credits()->delete();
        $moscow->credits()->createMany([
            ['name' => 'Вера Алентова', 'character' => 'Катерина', 'position' => 0],
            ['name' => 'Ирина Муравьёва', 'character' => 'Людмила', 'position' => 1],
            ['name' => 'Алексей Баталов', 'character' => 'Гоша', 'position' => 2],
        ]);

        $kinDza = Title::updateOrCreate(['slug' => 'kin-dza-dza'], [
            'tmdb_id' => 20873,
            'type' => 'movie',
            'name' => 'Кин-дза-дза!',
            'original_name' => 'Кин-дза-дза!',
            'description' => 'Сатирическая фантастика о путешествии на планету Плюк.',
            'release_year' => 1986,
            'release_date' => '1986-12-01',
            'rating' => 8.0,
            'popularity' => 700,
            'runtime_minutes' => 135,
            'country_code' => 'RU',
            'age_rating' => '12+',
            'is_published' => true,
        ]);
        $kinDza->genres()->sync([$genres['comedy']->id, $genres['sci-fi']->id]);

        $series = Title::updateOrCreate(['slug' => 'demo-serial'], [
            'type' => 'series',
            'name' => 'Демо-сериал',
            'original_name' => 'Demo Series',
            'description' => 'Безопасный демонстрационный сериал для проверки MVP.',
            'release_year' => 2026,
            'release_date' => '2026-01-01',
            'rating' => 7.5,
            'popularity' => 800,
            'country_code' => 'KG',
            'age_rating' => '12+',
            'is_published' => true,
        ]);
        $series->genres()->sync([$genres['drama']->id]);
        $season = $series->seasons()->updateOrCreate(['number' => 1], ['name' => 'Сезон 1', 'air_date' => '2026-01-01']);
        $episodeOne = $season->episodes()->updateOrCreate(['number' => 1], [
            'name' => 'Начало', 'description' => 'Первый демонстрационный эпизод.', 'runtime_minutes' => 12,
        ]);
        $season->episodes()->updateOrCreate(['number' => 2], [
            'name' => 'Продолжение', 'description' => 'Второй демонстрационный эпизод.', 'runtime_minutes' => 14,
        ]);

        $demo = Provider::updateOrCreate(['slug' => 'public-demo'], ['name' => 'Public Demo', 'is_active' => true]);
        $external = Provider::updateOrCreate(['slug' => 'external-demo'], ['name' => 'External Demo', 'is_active' => true]);
        $trailer = Provider::updateOrCreate(['slug' => 'youtube-demo'], ['name' => 'YouTube Demo', 'is_active' => true]);

        VideoSource::updateOrCreate(
            ['title_id' => $series->id, 'episode_id' => $episodeOne->id, 'provider_id' => $demo->id],
            [
                'mode' => VideoMode::Direct,
                'url' => 'https://storage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
                'legal_basis' => 'Публичный тестовый MP4 из официального Google sample bucket.',
                'countries' => ['KG', 'KZ', 'RU', 'UZ'],
                'priority' => 10,
                'is_active' => true,
            ]
        );
        VideoSource::updateOrCreate(
            ['title_id' => $moscow->id, 'episode_id' => null, 'provider_id' => $external->id],
            [
                'mode' => VideoMode::External,
                'url' => 'https://example.com/legal-catalog/moscow',
                'countries' => ['KG', 'KZ', 'RU'],
                'priority' => 20,
                'is_active' => true,
            ]
        );
        VideoSource::updateOrCreate(
            ['title_id' => $kinDza->id, 'episode_id' => null, 'provider_id' => $trailer->id],
            [
                'mode' => VideoMode::Trailer,
                'url' => 'https://www.youtube.com/watch?v=YE7VzlLtp-4',
                'countries' => null,
                'priority' => 30,
                'is_active' => true,
            ]
        );

        $cartoon = Title::updateOrCreate(['slug' => 'nu-pogodi'], [
            'type' => 'series',
            'name' => 'Ну, погоди!',
            'original_name' => 'Ну, погоди!',
            'description' => 'Классический советский мультсериал про Волка и Зайца.',
            'release_year' => 1969,
            'release_date' => '1969-06-14',
            'rating' => 8.6,
            'popularity' => 650,
            'country_code' => 'RU',
            'age_rating' => '0+',
            'is_published' => true,
        ]);
        $cartoon->genres()->sync([$genres['multfilm']->id, $genres['comedy']->id]);
        $cartoonSeason = $cartoon->seasons()->updateOrCreate(['number' => 1], ['name' => 'Выпуски', 'air_date' => '1969-06-14']);
        $cartoonSeason->episodes()->updateOrCreate(['number' => 1], [
            'name' => 'Выпуск 1', 'description' => 'Первый выпуск: пляж, город и погоня.', 'runtime_minutes' => 10,
        ]);

        $anime = Title::updateOrCreate(['slug' => 'demo-anime'], [
            'type' => 'series',
            'name' => 'Демо-аниме',
            'original_name' => 'Demo Anime',
            'description' => 'Демонстрационная карточка аниме без реального видеопотока.',
            'release_year' => 2024,
            'release_date' => '2024-04-01',
            'rating' => 7.2,
            'popularity' => 520,
            'country_code' => 'JP',
            'age_rating' => '16+',
            'is_published' => true,
        ]);
        $anime->genres()->sync([$genres['anime']->id]);
        VideoSource::updateOrCreate(
            ['title_id' => $anime->id, 'episode_id' => null, 'provider_id' => $trailer->id],
            [
                'mode' => VideoMode::Trailer,
                'url' => 'https://www.youtube.com/watch?v=YE7VzlLtp-4',
                'countries' => null,
                'priority' => 30,
                'is_active' => true,
            ]
        );

        $collection = Collection::updateOrCreate(['slug' => 'russian-classics'], [
            'name' => 'Русская классика',
            'description' => 'Демонстрационная подборка.',
            'is_published' => true,
        ]);
        $collection->titles()->sync([
            $moscow->id => ['position' => 1],
            $kinDza->id => ['position' => 2],
        ]);
    }
}
