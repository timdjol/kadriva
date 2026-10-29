<?php

namespace App\Filament\Resources {

    use App\Filament\Resources\TitleResource\Pages\ManageTitles;
    use App\Models\Title;
    use Filament\Actions\BulkActionGroup;
    use Filament\Actions\DeleteBulkAction;
    use Filament\Actions\EditAction;
    use Filament\Forms\Components\Select;
    use Filament\Forms\Components\Textarea;
    use Filament\Forms\Components\TextInput;
    use Filament\Forms\Components\Toggle;
    use Filament\Resources\Resource;
    use Filament\Schemas\Schema;
    use Filament\Tables\Columns\TextColumn;
    use Filament\Tables\Table;

    class TitleResource extends Resource
    {
        protected static ?string $model = Title::class;

        public static function form(Schema $schema): Schema
        {
            return $schema->components([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Select::make('type')->options(['movie' => 'Фильм', 'series' => 'Сериал'])->required(),
                Textarea::make('description')->columnSpanFull(),
                TextInput::make('release_year')->numeric(),
                TextInput::make('tmdb_id')->numeric(),
                TextInput::make('rating')->numeric()->minValue(0)->maxValue(10),
                TextInput::make('age_rating'),
                TextInput::make('popularity')->numeric()->default(0),
                Select::make('genres')->relationship('genres', 'name')->multiple()->preload(),
                Toggle::make('is_published'),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('type'),
                TextColumn::make('release_year')->sortable(),
                TextColumn::make('rating')->sortable(),
            ])->recordActions([EditAction::make()])
                ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
        }

        public static function getPages(): array
        {
            return ['index' => ManageTitles::route('/')];
        }
    }
}

namespace App\Filament\Resources\TitleResource\Pages {

    use App\Filament\Resources\TitleResource;
    use App\Services\Tmdb\TmdbImporter;
    use Filament\Actions\Action;
    use Filament\Actions\CreateAction;
    use Filament\Forms\Components\Select;
    use Filament\Forms\Components\TextInput;
    use Filament\Forms\Components\Toggle;
    use Filament\Notifications\Notification;
    use Filament\Resources\Pages\ManageRecords;
    use Throwable;

    class ManageTitles extends ManageRecords
    {
        protected static string $resource = TitleResource::class;

        protected function getHeaderActions(): array
        {
            return [
                CreateAction::make(),
                Action::make('importTmdb')
                    ->label('Импорт TMDB')
                    ->form([
                        Select::make('type')->options(['movie' => 'Фильм', 'series' => 'Сериал'])->required(),
                        TextInput::make('tmdb_id')->numeric()->required()->label('TMDB ID'),
                        Toggle::make('publish')->label('Опубликовать сразу'),
                    ])
                    ->action(function (array $data): void {
                        try {
                            $title = app(TmdbImporter::class)->import((int) $data['tmdb_id'], $data['type'], (bool) ($data['publish'] ?? false));
                            Notification::make()->title('Импортировано: '.$title->name)->success()->send();
                        } catch (Throwable $exception) {
                            Notification::make()->title('Импорт не выполнен')->body($exception->getMessage())->danger()->send();
                        }
                    }),
            ];
        }
    }
}
