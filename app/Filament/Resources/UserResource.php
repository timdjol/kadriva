<?php

namespace App\Filament\Resources {

    use App\Filament\Resources\UserResource\Pages\ManageUsers;
    use App\Models\User;
    use Filament\Actions\BulkActionGroup;
    use Filament\Actions\DeleteBulkAction;
    use Filament\Actions\EditAction;
    use Filament\Forms\Components\Select;
    use Filament\Forms\Components\TextInput;
    use Filament\Resources\Resource;
    use Filament\Schemas\Schema;
    use Filament\Tables\Columns\TextColumn;
    use Filament\Tables\Table;
    use Illuminate\Support\Facades\Hash;

    class UserResource extends Resource
    {
        protected static ?string $model = User::class;

        public static function canViewAny(): bool
        {
            return auth()->user()?->role === 'admin';
        }

        public static function form(Schema $schema): Schema
        {
            return $schema->components([
                TextInput::make('name')->required(),
                TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                TextInput::make('password')->password()->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                Select::make('role')->options(['admin' => 'Admin', 'editor' => 'Editor', 'user' => 'User'])->required(),
                Select::make('country_code')->options(['KG' => 'KG', 'KZ' => 'KZ', 'RU' => 'RU', 'UZ' => 'UZ'])->required(),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('role')->badge(),
                TextColumn::make('country_code'),
            ])->recordActions([EditAction::make()])
                ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
        }

        public static function getPages(): array
        {
            return ['index' => ManageUsers::route('/')];
        }
    }
}

namespace App\Filament\Resources\UserResource\Pages {

    use App\Filament\Resources\UserResource;
    use Filament\Actions\CreateAction;
    use Filament\Resources\Pages\ManageRecords;

    class ManageUsers extends ManageRecords
    {
        protected static string $resource = UserResource::class;

        protected function getHeaderActions(): array
        {
            return [CreateAction::make()];
        }
    }
}
