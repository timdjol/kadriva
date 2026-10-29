<?php

namespace App\Filament\Resources {

    use App\Filament\Resources\ProviderResource\Pages\ManageProviders;
    use App\Models\Provider;
    use Filament\Actions\BulkActionGroup;
    use Filament\Actions\DeleteBulkAction;
    use Filament\Actions\EditAction;
    use Filament\Forms\Components\TextInput;
    use Filament\Forms\Components\Toggle;
    use Filament\Resources\Resource;
    use Filament\Schemas\Schema;
    use Filament\Tables\Columns\IconColumn;
    use Filament\Tables\Columns\TextColumn;
    use Filament\Tables\Table;

    class ProviderResource extends Resource
    {
        protected static ?string $model = Provider::class;

        public static function form(Schema $schema): Schema
        {
            return $schema->components([
                TextInput::make('name')->required(),
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Toggle::make('is_active')->default(true),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('slug'),
                IconColumn::make('is_active')->boolean(),
            ])->recordActions([EditAction::make()])
                ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
        }

        public static function getPages(): array
        {
            return ['index' => ManageProviders::route('/')];
        }
    }
}

namespace App\Filament\Resources\ProviderResource\Pages {

    use App\Filament\Resources\ProviderResource;
    use Filament\Actions\CreateAction;
    use Filament\Resources\Pages\ManageRecords;

    class ManageProviders extends ManageRecords
    {
        protected static string $resource = ProviderResource::class;

        protected function getHeaderActions(): array
        {
            return [CreateAction::make()];
        }
    }
}
