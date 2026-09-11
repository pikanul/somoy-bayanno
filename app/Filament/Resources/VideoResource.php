<?php

namespace App\Filament\Resources;

use App\Enums\VideoProvider;
use App\Enums\VideoStatus;
use App\Filament\Resources\VideoResource\Pages;
use App\Models\Author;
use App\Models\Category;
use App\Models\Video;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
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

class VideoResource extends Resource
{
    protected static ?string $model = Video::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedVideoCamera;

    protected static ?string $navigationLabel = 'Videos';

    protected static ?string $modelLabel = 'Video';

    protected static ?string $pluralModelLabel = 'Videos';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $slug = 'videos';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Video')
                    ->schema([
                        TextInput::make('title_bn')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description_bn')
                            ->label('Description')
                            ->rows(4)
                            ->columnSpanFull(),
                        Select::make('provider')
                            ->options(VideoProvider::options())
                            ->required()
                            ->native(false),
                        TextInput::make('video_url')
                            ->label('Video URL')
                            ->required()
                            ->url()
                            ->maxLength(2048),
                        TextInput::make('provider_video_id')
                            ->label('Provider video ID')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('thumbnail')
                            ->label('Thumbnail URL')
                            ->url()
                            ->maxLength(2048),
                        TextInput::make('duration')
                            ->label('Duration seconds')
                            ->numeric()
                            ->minValue(1),
                    ])
                    ->columns(2),
                Section::make('Classification')
                    ->schema([
                        Select::make('author_id')
                            ->label('Author')
                            ->options(fn (): array => Author::query()->orderBy('name_bn')->pluck('name_bn', 'id')->all())
                            ->searchable()
                            ->native(false),
                        Select::make('category_id')
                            ->label('Category')
                            ->options(fn (): array => Category::query()->orderBy('sort_order')->orderBy('name_bn')->pluck('name_bn', 'id')->all())
                            ->searchable()
                            ->native(false),
                        Select::make('status')
                            ->options(VideoStatus::options())
                            ->default(VideoStatus::Draft->value)
                            ->required()
                            ->native(false),
                        DateTimePicker::make('published_at')
                            ->label('Published time')
                            ->seconds(false),
                    ])
                    ->columns(2),
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
                TextColumn::make('title_bn')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->limit(60),
                TextColumn::make('provider')
                    ->badge()
                    ->formatStateUsing(fn (VideoProvider|string|null $state): string => $state instanceof VideoProvider ? $state->label() : ucfirst((string) $state))
                    ->sortable(),
                TextColumn::make('category.name_bn')
                    ->label('Category')
                    ->sortable(),
                TextColumn::make('author.name_bn')
                    ->label('Author')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (VideoStatus|string|null $state): string => $state instanceof VideoStatus ? $state->label() : ucfirst((string) $state))
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
                SelectFilter::make('provider')
                    ->options(VideoProvider::options()),
                SelectFilter::make('status')
                    ->options(VideoStatus::options()),
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
        return parent::getEloquentQuery()->with(['author', 'category']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVideos::route('/'),
            'create' => Pages\CreateVideo::route('/create'),
            'edit' => Pages\EditVideo::route('/{record}/edit'),
        ];
    }
}
