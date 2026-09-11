<?php

namespace App\Filament\Resources;

use App\Enums\BreakingNewsStatus;
use App\Enums\BreakingNewsTargetType;
use App\Filament\Resources\BreakingNewsResource\Pages;
use App\Models\Article;
use App\Models\BreakingNews;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class BreakingNewsResource extends Resource
{
    protected static ?string $model = BreakingNews::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $navigationLabel = 'Breaking News';

    protected static ?string $modelLabel = 'Breaking news';

    protected static ?string $pluralModelLabel = 'Breaking News';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $slug = 'breaking-news';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ticker')
                    ->schema([
                        TextInput::make('headline_bn')
                            ->label('Headline')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('target_type')
                            ->label('Target')
                            ->options(BreakingNewsTargetType::options())
                            ->default(BreakingNewsTargetType::None->value)
                            ->required()
                            ->native(false)
                            ->live(),
                        Select::make('article_id')
                            ->label('Article')
                            ->options(fn (): array => Article::query()->latest()->limit(200)->pluck('headline_bn', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->visible(fn ($get): bool => $get('target_type') === BreakingNewsTargetType::Article->value)
                            ->required(fn ($get): bool => $get('target_type') === BreakingNewsTargetType::Article->value),
                        TextInput::make('external_url')
                            ->label('External URL')
                            ->url()
                            ->maxLength(2048)
                            ->visible(fn ($get): bool => $get('target_type') === BreakingNewsTargetType::External->value)
                            ->required(fn ($get): bool => $get('target_type') === BreakingNewsTargetType::External->value),
                        TextInput::make('priority')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                        Select::make('status')
                            ->options(BreakingNewsStatus::options())
                            ->default(BreakingNewsStatus::Inactive->value)
                            ->required()
                            ->native(false),
                        DateTimePicker::make('starts_at')
                            ->label('Starts at')
                            ->seconds(false),
                        DateTimePicker::make('ends_at')
                            ->label('Ends at')
                            ->seconds(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('headline_bn')
                    ->label('Headline')
                    ->searchable()
                    ->limit(70),
                TextColumn::make('target_type')
                    ->label('Target')
                    ->badge()
                    ->formatStateUsing(fn (BreakingNewsTargetType|string|null $state): string => $state instanceof BreakingNewsTargetType ? $state->label() : ucfirst((string) $state)),
                TextColumn::make('priority')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (BreakingNewsStatus|string|null $state): string => $state instanceof BreakingNewsStatus ? $state->label() : ucfirst((string) $state))
                    ->sortable(),
                IconColumn::make('is_active_now')
                    ->label('Live')
                    ->getStateUsing(fn (BreakingNews $record): bool => $record->isCurrentlyActive())
                    ->boolean(),
                TextColumn::make('starts_at')
                    ->label('Starts')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Ends')
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
                    ->options(BreakingNewsStatus::options()),
                SelectFilter::make('target_type')
                    ->label('Target')
                    ->options(BreakingNewsTargetType::options()),
                Filter::make('currently_active')
                    ->label('Currently active')
                    ->query(fn (Builder $query): Builder => $query->currentlyActive()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->reorderable('priority')
            ->defaultSort('priority', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['article']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBreakingNews::route('/'),
            'create' => Pages\CreateBreakingNews::route('/create'),
            'edit' => Pages\EditBreakingNews::route('/{record}/edit'),
        ];
    }
}
