<?php

namespace App\Filament\Resources {

    use App\Filament\Resources\GenreResource\Pages\ManageGenres;
    use App\Models\Genre;
    use Filament\Actions\BulkActionGroup;
    use Filament\Actions\DeleteBulkAction;
    use Filament\Actions\EditAction;
    use Filament\Forms\Components\TextInput;
    use Filament\Resources\Resource;
    use Filament\Schemas\Schema;
    use Filament\Tables\Columns\TextColumn;
    use Filament\Tables\Table;

    class GenreResource extends Resource
    {
        protected static ?string $model = Genre::class;

        public static function form(Schema $schema): Schema
        {
            return $schema->components([
                TextInput::make('name')->required(),
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug'),
            ])->recordActions([EditAction::make()])
                ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
        }

        public static function getPages(): array
        {
            return ['index' => ManageGenres::route('/')];
        }
    }
}

namespace App\Filament\Resources\GenreResource\Pages {

    use App\Filament\Resources\GenreResource;
    use Filament\Actions\CreateAction;
    use Filament\Resources\Pages\ManageRecords;

    class ManageGenres extends ManageRecords
    {
        protected static string $resource = GenreResource::class;

        protected function getHeaderActions(): array
        {
            return [CreateAction::make()];
        }
    }
}
