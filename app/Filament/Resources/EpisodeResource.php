<?php

namespace App\Filament\Resources {

    use App\Filament\Resources\EpisodeResource\Pages\ManageEpisodes;
    use App\Models\Episode;
    use Filament\Actions\CreateAction;
    use Filament\Actions\DeleteAction;
    use Filament\Actions\EditAction;
    use Filament\Forms\Components\DatePicker;
    use Filament\Forms\Components\Select;
    use Filament\Forms\Components\Textarea;
    use Filament\Forms\Components\TextInput;
    use Filament\Resources\Resource;
    use Filament\Schemas\Schema;
    use Filament\Tables\Columns\TextColumn;
    use Filament\Tables\Table;

    class EpisodeResource extends Resource
    {
        protected static ?string $model = Episode::class;

        public static function form(Schema $schema): Schema
        {
            return $schema->components([
                Select::make('season_id')->relationship('season', 'name')->searchable()->preload()->required(),
                TextInput::make('number')->numeric()->minValue(1)->required(),
                TextInput::make('name')->required(),
                Textarea::make('description')->columnSpanFull(),
                TextInput::make('runtime_minutes')->numeric()->minValue(1),
                TextInput::make('still_url')->url(),
                DatePicker::make('air_date'),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('season.title.name')->label('Тайтл')->searchable(),
                TextColumn::make('season.number')->label('Сезон'),
                TextColumn::make('number')->label('Серия')->sortable(),
                TextColumn::make('name')->searchable(),
            ])->headerActions([CreateAction::make()])
                ->recordActions([EditAction::make(), DeleteAction::make()]);
        }

        public static function getPages(): array
        {
            return ['index' => ManageEpisodes::route('/')];
        }
    }
}

namespace App\Filament\Resources\EpisodeResource\Pages {

    use App\Filament\Resources\EpisodeResource;
    use Filament\Resources\Pages\ManageRecords;

    class ManageEpisodes extends ManageRecords
    {
        protected static string $resource = EpisodeResource::class;
    }
}
