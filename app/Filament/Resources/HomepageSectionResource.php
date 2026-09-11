<?php

namespace App\Filament\Resources;

use App\Enums\HomepageSectionKey;
use App\Enums\HomepageSectionStatus;
use App\Filament\Resources\HomepageSectionResource\Pages;
use App\Models\Article;
use App\Models\HomepageSection;
use App\Services\HomepageManagerService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class HomepageSectionResource extends Resource
{
    protected static ?string $model = HomepageSection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Homepage Manager';

    protected static ?string $modelLabel = 'Homepage section';

    protected static ?string $pluralModelLabel = 'Homepage Manager';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $slug = 'homepage-manager';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Section')
                    ->schema([
                        Select::make('key')
                            ->label('Position')
                            ->options(HomepageSectionKey::options())
                            ->required()
                            ->native(false)
                            ->unique(ignoreRecord: true),
                        TextInput::make('title')
                            ->maxLength(255),
                        Select::make('status')
                            ->options(HomepageSectionStatus::options())
                            ->default(HomepageSectionStatus::Draft->value)
                            ->required()
                            ->native(false),
                        TextInput::make('sort_order')
                            ->label('Order')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Stories')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Select::make('article_id')
                                    ->label('Article')
                                    ->options(fn (): array => Article::query()
                                        ->publiclyVisible()
                                        ->latest('published_at')
                                        ->limit(250)
                                        ->pluck('headline_bn', 'id')
                                        ->all())
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->distinct()
                                    ->native(false),
                                TextInput::make('label')
                                    ->maxLength(255),
                                DateTimePicker::make('starts_at')
                                    ->label('Starts')
                                    ->seconds(false),
                                DateTimePicker::make('ends_at')
                                    ->label('Ends')
                                    ->seconds(false),
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
                            ->itemLabel(fn (array $state): ?string => isset($state['article_id']) ? 'Article #'.$state['article_id'] : 'Story')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('Position')
                    ->formatStateUsing(fn (HomepageSectionKey|string|null $state): string => $state instanceof HomepageSectionKey ? $state->label() : ucfirst((string) $state))
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('items_count')
                    ->label('Stories')
                    ->counts('items')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (HomepageSectionStatus|string|null $state): string => $state instanceof HomepageSectionStatus ? $state->label() : ucfirst((string) $state))
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(HomepageSectionStatus::options()),
            ])
            ->recordActions([
                Action::make('publish')
                    ->icon(Heroicon::OutlinedRocketLaunch)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (HomepageSection $record): bool => Auth::user()?->can('update', $record) ?? false)
                    ->action(fn (HomepageSection $record): HomepageSection => app(HomepageManagerService::class)->publish(Auth::user(), $record)),
                Action::make('preview')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (): string => route('home'))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('items');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomepageSections::route('/'),
            'create' => Pages\CreateHomepageSection::route('/create'),
            'edit' => Pages\EditHomepageSection::route('/{record}/edit'),
        ];
    }
}
