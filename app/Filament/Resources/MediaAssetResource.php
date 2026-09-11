<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MediaAssetResource\Pages;
use App\Models\MediaAsset;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class MediaAssetResource extends Resource
{
    protected static ?string $model = MediaAsset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $navigationLabel = 'Media Library';

    protected static ?string $modelLabel = 'Media item';

    protected static ?string $pluralModelLabel = 'Media Library';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $slug = 'media-library';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Image')
                    ->schema([
                        Select::make('source_mode')
                            ->label('Source')
                            ->options([
                                'upload' => 'Upload local image',
                                'external' => 'Import HTTPS URL',
                            ])
                            ->default('upload')
                            ->required()
                            ->native(false)
                            ->visibleOn('create')
                            ->live(),
                        FileUpload::make('local_upload')
                            ->label('Local image')
                            ->image()
                            ->storeFiles(false)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
                            ->maxSize((int) ceil(config('media-library.max_bytes') / 1024))
                            ->visible(fn ($get): bool => $get('source_mode') === 'upload')
                            ->required(fn ($get): bool => $get('source_mode') === 'upload')
                            ->dehydrated(true),
                        TextInput::make('external_url_input')
                            ->label('HTTPS image URL')
                            ->url()
                            ->maxLength(2048)
                            ->visible(fn ($get): bool => $get('source_mode') === 'external')
                            ->required(fn ($get): bool => $get('source_mode') === 'external')
                            ->dehydrated(true),
                    ])
                    ->visibleOn('create'),
                Section::make('Metadata')
                    ->schema([
                        TextInput::make('alt_text')
                            ->label('Alt text')
                            ->maxLength(255),
                        TextInput::make('caption')
                            ->maxLength(255),
                        TextInput::make('credit')
                            ->maxLength(255),
                        TextInput::make('photographer')
                            ->maxLength(255),
                        TextInput::make('source_name')
                            ->label('Source name')
                            ->maxLength(255),
                        TextInput::make('source_url')
                            ->label('Source URL')
                            ->url()
                            ->maxLength(2048),
                        TextInput::make('copyright')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('File details')
                    ->schema([
                        TextInput::make('original_name')
                            ->disabled(),
                        TextInput::make('mime_type')
                            ->disabled(),
                        TextInput::make('file_size')
                            ->disabled(),
                        TextInput::make('width')
                            ->disabled(),
                        TextInput::make('height')
                            ->disabled(),
                        TextInput::make('path')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('path')
                    ->label('Image')
                    ->disk(fn (MediaAsset $record): ?string => $record->storage_disk)
                    ->square(),
                TextColumn::make('original_name')
                    ->label('Name')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('alt_text')
                    ->label('Alt')
                    ->searchable()
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('caption')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('mime_type')
                    ->label('MIME')
                    ->sortable(),
                TextColumn::make('width')
                    ->label('Size')
                    ->formatStateUsing(fn (MediaAsset $record): string => "{$record->width} x {$record->height}")
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMediaAssets::route('/'),
            'create' => Pages\CreateMediaAsset::route('/create'),
            'edit' => Pages\EditMediaAsset::route('/{record}/edit'),
        ];
    }
}
