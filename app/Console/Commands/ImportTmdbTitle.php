<?php

namespace App\Console\Commands;

use App\Services\Tmdb\TmdbImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportTmdbTitle extends Command
{
    protected $signature = 'tmdb:import {type : movie или series} {id : TMDB ID} {--publish : сразу опубликовать карточку}';

    protected $description = 'Импортировать одну запись TMDB в локальный каталог';

    public function handle(TmdbImporter $importer): int
    {
        try {
            $title = $importer->import((int) $this->argument('id'), (string) $this->argument('type'), (bool) $this->option('publish'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Импортировано: {$title->name} (#{$title->id})");

        return self::SUCCESS;
    }
}
