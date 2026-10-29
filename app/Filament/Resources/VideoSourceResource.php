<?php

namespace App\Filament\Resources {

    use App\Domain\Video\VideoMode;
    use App\Filament\Resources\VideoSourceResource\Pages\ManageVideoSources;
    use App\Models\VideoSource;
    use Filament\Actions\BulkActionGroup;
    use Filament\Actions\DeleteBulkAction;
    use Filament\Actions\EditAction;
    use Filament\Forms\Components\Checkbox;
    use Filament\Forms\Components\Select;
    use Filament\Forms\Components\TextInput;
    use Filament\Forms\Components\Toggle;
    use Filament\Resources\Resource;
    use Filament\Schemas\Components\Utilities\Get;
    use Filament\Schemas\Schema;
    use Filament\Tables\Columns\TextColumn;
    use Filament\Tables\Table;

    class VideoSourceResource extends Resource
    {
        protected static ?string $model = VideoSource::class;

        public static function form(Schema $schema): Schema
        {
            return $schema->components([
                Select::make('title_id')->relationship('title', 'name')->searchable()->preload()->required(),
                Select::make('episode_id')->relationship('episode', 'name')->searchable()->preload(),
                Select::make('provider_id')->relationship('provider', 'name')->preload()->required(),
                Select::make('mode')->options(collect(VideoMode::cases())->mapWithKeys(fn ($mode) => [$mode->value => $mode->value]))->live()->required(),
                TextInput::make('url')->url()->startsWith('https://')->required(),
                TextInput::make('legal_basis')
                    ->required(fn (Get $get): bool => in_array($get('mode'), [VideoMode::Direct->value, VideoMode::Embed->value], true)),
                Checkbox::make('legal_confirmed')
                    ->label('Подтверждаю право на встраивание/прямое воспроизведение')
                    ->dehydrated(false)
                    ->visible(fn (Get $get): bool => in_array($get('mode'), [VideoMode::Direct->value, VideoMode::Embed->value], true))
                    ->required(fn (Get $get): bool => in_array($get('mode'), [VideoMode::Direct->value, VideoMode::Embed->value], true)),
                TextInput::make('priority')->numeric()->default(100)->required(),
                Toggle::make('is_active')->default(true),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('title.name')->searchable(),
                TextColumn::make('provider.name'),
                TextColumn::make('mode')->badge(),
                TextColumn::make('priority')->sortable(),
            ])->recordActions([EditAction::make()])
                ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
        }

        public static function getPages(): array
        {
            return ['index' => ManageVideoSources::route('/')];
        }
    }
}

namespace App\Filament\Resources\VideoSourceResource\Pages {

    use App\Filament\Resources\VideoSourceResource;
    use Filament\Actions\CreateAction;
    use Filament\Resources\Pages\ManageRecords;

    class ManageVideoSources extends ManageRecords
    {
        protected static string $resource = VideoSourceResource::class;

        protected function getHeaderActions(): array
        {
            return [CreateAction::make()];
        }
    }
}
