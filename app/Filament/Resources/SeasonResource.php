<?php

namespace App\Filament\Resources {

    use App\Filament\Resources\SeasonResource\Pages\ManageSeasons;
    use App\Models\Season;
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

    class SeasonResource extends Resource
    {
        protected static ?string $model = Season::class;

        public static function form(Schema $schema): Schema
        {
            return $schema->components([
                Select::make('title_id')->relationship('title', 'name')->searchable()->preload()->required(),
                TextInput::make('number')->numeric()->minValue(0)->required(),
                TextInput::make('name'),
                Textarea::make('description')->columnSpanFull(),
                TextInput::make('poster_url')->url(),
                DatePicker::make('air_date'),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('title.name')->searchable(),
                TextColumn::make('number')->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('air_date')->date(),
            ])->headerActions([CreateAction::make()])
                ->recordActions([EditAction::make(), DeleteAction::make()]);
        }

        public static function getPages(): array
        {
            return ['index' => ManageSeasons::route('/')];
        }
    }
}

namespace App\Filament\Resources\SeasonResource\Pages {

    use App\Filament\Resources\SeasonResource;
    use Filament\Resources\Pages\ManageRecords;

    class ManageSeasons extends ManageRecords
    {
        protected static string $resource = SeasonResource::class;
    }
}
