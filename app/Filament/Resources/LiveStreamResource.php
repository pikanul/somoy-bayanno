<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LiveStreamResource\Pages;
use App\Models\LiveStream;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class LiveStreamResource extends Resource
{
    protected static ?string $model = LiveStream::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static ?string $navigationLabel = 'Live Streams';

    protected static ?string $modelLabel = 'Live Stream';

    protected static ?string $pluralModelLabel = 'Live Streams';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $slug = 'live-streams';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Live Stream')
                    ->schema([
                        TextInput::make('title_bn')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Select::make('provider')
                            ->options([
                                'youtube' => 'YouTube',
                                'facebook' => 'Facebook',
                                'vimeo' => 'Vimeo',
                                'hls' => 'HLS / M3U8',
                                'mp4' => 'MP4',
                                'embed' => 'Embed URL',
                            ])
                            ->default('youtube')
                            ->required()
                            ->native(false),
                        TextInput::make('stream_url')
                            ->label('Live video/link URL')
                            ->required()
                            ->url()
                            ->maxLength(2048)
                            ->columnSpanFull(),
                        TextInput::make('embed_url')
                            ->label('Optional embed/playback URL')
                            ->url()
                            ->maxLength(2048)
                            ->columnSpanFull(),
                        TextInput::make('poster_url')
                            ->label('Poster image URL')
                            ->url()
                            ->maxLength(2048),
                        TextInput::make('status_text')
                            ->label('On-screen status')
                            ->maxLength(255),
                        Textarea::make('description_bn')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Playback')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active live stream')
                            ->default(false),
                        Toggle::make('autoplay')
                            ->label('Autoplay')
                            ->default(true),
                        Toggle::make('muted')
                            ->label('Muted autoplay')
                            ->default(true),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(100)
                            ->required(),
                        DateTimePicker::make('starts_at')
                            ->seconds(false),
                        DateTimePicker::make('ends_at')
                            ->seconds(false),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title_bn')
                    ->label('Title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('provider')
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('status_text')
                    ->label('Status')
                    ->toggleable(),
                TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('sort_order');
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();

        return $data;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLiveStreams::route('/'),
            'create' => Pages\CreateLiveStream::route('/create'),
            'edit' => Pages\EditLiveStream::route('/{record}/edit'),
        ];
    }
}
