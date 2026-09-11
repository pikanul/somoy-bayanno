<?php

namespace App\Filament\Resources;

use App\Enums\GalleryStatus;
use App\Filament\Resources\GalleryResource\Pages;
use App\Models\Author;
use App\Models\Category;
use App\Models\Gallery;
use App\Models\MediaAsset;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class GalleryResource extends Resource
{
    protected static ?string $model = Gallery::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?string $navigationLabel = 'Photo Galleries';

    protected static ?string $modelLabel = 'Photo gallery';

    protected static ?string $pluralModelLabel = 'Photo Galleries';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $slug = 'photo-galleries';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gallery')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->alphaDash()
                            ->unique(ignoreRecord: true),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Select::make('cover_image_id')
                            ->label('Cover image')
                            ->options(fn (): array => self::mediaOptions())
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Select::make('author_id')
                            ->label('Author')
                            ->options(fn (): array => Author::query()->orderBy('name_bn')->pluck('name_bn', 'id')->all())
                            ->searchable()
                            ->native(false),
                        TextInput::make('photographer')
                            ->maxLength(255),
                        Select::make('category_id')
                            ->label('Category')
                            ->options(fn (): array => Category::query()->orderBy('sort_order')->orderBy('name_bn')->pluck('name_bn', 'id')->all())
                            ->searchable()
                            ->native(false),
                        Select::make('status')
                            ->options(GalleryStatus::options())
                            ->default(GalleryStatus::Draft->value)
                            ->required()
                            ->native(false),
                        DateTimePicker::make('published_at')
                            ->label('Published time')
                            ->seconds(false),
                    ])
                    ->columns(3),
                Section::make('Images')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Select::make('media_id')
                                    ->label('Image')
                                    ->options(fn (): array => self::mediaOptions())
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->distinct()
                                    ->native(false),
                                TextInput::make('caption')
                                    ->maxLength(255),
                                TextInput::make('credit')
                                    ->maxLength(255),
                                TextInput::make('sort_order')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->hidden(),
                            ])
                            ->orderColumn('sort_order')
                            ->reorderable()
                            ->reorderableWithDragAndDrop()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => isset($state['media_id']) ? 'Image #'.$state['media_id'] : 'Image')
                            ->columnSpanFull(),
                    ]),
                Section::make('SEO')
                    ->schema([
                        TextInput::make('seo_title')
                            ->label('SEO title')
                            ->maxLength(255),
                        Textarea::make('seo_description')
                            ->label('SEO description')
                            ->rows(3),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(60),
                TextColumn::make('category.name_bn')
                    ->label('Category')
                    ->sortable(),
                TextColumn::make('author.name_bn')
                    ->label('Author')
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Images')
                    ->counts('items')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (GalleryStatus|string|null $state): string => $state instanceof GalleryStatus ? $state->label() : ucfirst((string) $state))
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(GalleryStatus::options()),
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn (): array => Category::query()->orderBy('sort_order')->orderBy('name_bn')->pluck('name_bn', 'id')->all()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['author', 'category', 'coverImage'])->withCount('items');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGalleries::route('/'),
            'create' => Pages\CreateGallery::route('/create'),
            'edit' => Pages\EditGallery::route('/{record}/edit'),
        ];
    }

    /** @return array<int, string> */
    private static function mediaOptions(): array
    {
        return MediaAsset::query()
            ->where('type', 'image')
            ->latest()
            ->limit(200)
            ->get()
            ->mapWithKeys(fn (MediaAsset $mediaAsset): array => [
                $mediaAsset->getKey() => $mediaAsset->alt_text
                    ?: $mediaAsset->caption
                    ?: $mediaAsset->original_name
                    ?: 'Media #'.$mediaAsset->getKey(),
            ])
            ->all();
    }
}
