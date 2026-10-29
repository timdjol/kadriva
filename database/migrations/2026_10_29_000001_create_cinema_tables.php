<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('titles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tmdb_id')->nullable();
            $table->string('type')->index();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('original_name')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('release_year')->nullable()->index();
            $table->date('release_date')->nullable()->index();
            $table->decimal('rating', 3, 1)->default(0)->index();
            $table->unsignedBigInteger('popularity')->default(0)->index();
            $table->unsignedInteger('runtime_minutes')->nullable();
            $table->string('poster_url')->nullable();
            $table->string('backdrop_url')->nullable();
            $table->char('country_code', 2)->nullable()->index();
            $table->string('status')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
            $table->unique(['tmdb_id', 'type']);
        });

        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tmdb_id')->nullable()->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('genre_title', function (Blueprint $table) {
            $table->foreignId('genre_id')->constrained()->cascadeOnDelete();
            $table->foreignId('title_id')->constrained()->cascadeOnDelete();
            $table->primary(['genre_id', 'title_id']);
        });

        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('title_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('tmdb_id')->nullable();
            $table->unsignedSmallInteger('number');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('poster_url')->nullable();
            $table->date('air_date')->nullable();
            $table->timestamps();
            $table->unique(['title_id', 'number']);
        });

        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('tmdb_id')->nullable()->index();
            $table->unsignedSmallInteger('number');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('runtime_minutes')->nullable();
            $table->string('still_url')->nullable();
            $table->date('air_date')->nullable();
            $table->timestamps();
            $table->unique(['season_id', 'number']);
        });

        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('homepage_url')->nullable();
            $table->string('integration_type')->default('external');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('video_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('title_id')->constrained()->cascadeOnDelete();
            $table->foreignId('episode_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('mode')->index();
            $table->text('url');
            $table->string('legal_basis')->nullable();
            $table->json('countries')->nullable();
            $table->json('audio_languages')->nullable();
            $table->json('subtitle_languages')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['title_id', 'episode_id', 'priority']);
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('title_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'title_id']);
        });

        Schema::create('watch_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('title_id')->constrained()->cascadeOnDelete();
            $table->foreignId('episode_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position_seconds');
            $table->unsignedInteger('duration_seconds');
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamp('last_watched_at');
            $table->timestamps();
            $table->unique(['user_id', 'title_id', 'episode_id']);
        });

        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('collection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('title_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['collection_id', 'title_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->nullableMorphs('auditable');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('collection_items');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('watch_progress');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('video_sources');
        Schema::dropIfExists('providers');
        Schema::dropIfExists('episodes');
        Schema::dropIfExists('seasons');
        Schema::dropIfExists('genre_title');
        Schema::dropIfExists('genres');
        Schema::dropIfExists('titles');
    }
};
