<?php

namespace App\Filament\Resources {

    use App\Filament\Resources\CollectionResource\Pages\ManageCollections;
    use App\Models\Collection;
    use Filament\Actions\BulkActionGroup;
    use Filament\Actions\DeleteBulkAction;
    use Filament\Actions\EditAction;
    use Filament\Forms\Components\Select;
    use Filament\Forms\Components\Textarea;
    use Filament\Forms\Components\TextInput;
    use Filament\Forms\Components\Toggle;
    use Filament\Resources\Resource;
    use Filament\Schemas\Schema;
    use Filament\Tables\Columns\IconColumn;
    use Filament\Tables\Columns\TextColumn;
    use Filament\Tables\Table;

    class CollectionResource extends Resource
    {
        protected static ?string $model = Collection::class;

        public static function form(Schema $schema): Schema
        {
            return $schema->components([
                TextInput::make('name')->required(),
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Textarea::make('description'),
                Select::make('titles')->relationship('titles', 'name')->multiple()->searchable()->preload(),
                Toggle::make('is_published'),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('titles_count')->counts('titles'),
                IconColumn::make('is_published')->boolean(),
            ])->recordActions([EditAction::make()])
                ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
        }

        public static function getPages(): array
        {
            return ['index' => ManageCollections::route('/')];
        }
    }
}

namespace App\Filament\Resources\CollectionResource\Pages {

    use App\Filament\Resources\CollectionResource;
    use Filament\Actions\CreateAction;
    use Filament\Resources\Pages\ManageRecords;

    class ManageCollections extends ManageRecords
    {
        protected static string $resource = CollectionResource::class;

        protected function getHeaderActions(): array
        {
            return [CreateAction::make()];
        }
    }
}
