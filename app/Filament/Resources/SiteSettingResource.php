<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteSettingResource\Pages;
use App\Models\SiteSetting;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SiteSettingResource extends Resource
{
    protected static ?string $model = SiteSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Footer Settings';

    protected static ?string $modelLabel = 'Footer Setting';

    protected static ?string $pluralModelLabel = 'Footer Settings';

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $slug = 'settings';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label('Key')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('label')
                    ->label('Label')
                    ->required()
                    ->maxLength(255),
                TextInput::make('group')
                    ->label('Group')
                    ->maxLength(255)
                    ->default('footer'),
                Select::make('type')
                    ->label('Type')
                    ->options([
                        'boolean' => 'Boolean',
                        'email' => 'Email',
                        'text' => 'Text',
                        'textarea' => 'Long text',
                        'url' => 'URL',
                    ])
                    ->default('text')
                    ->required()
                    ->native(false),
                Toggle::make('is_public')
                    ->label('Publicly visible')
                    ->default(true),
                Textarea::make('value')
                    ->label('Value')
                    ->rules(fn (callable $get): array => match ($get('type')) {
                        'email' => ['nullable', 'email'],
                        'url' => ['nullable', 'url'],
                        default => ['nullable', 'string'],
                    })
                    ->rows(5)
                    ->columnSpanFull(),
                TextInput::make('sort_order')
                    ->label('Sort order')
                    ->numeric()
                    ->minValue(0)
                    ->default(100)
                    ->required(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label('Label')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('key')
                    ->label('Key')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('value')
                    ->label('Value')
                    ->limit(70)
                    ->wrap(),
                TextColumn::make('group')
                    ->label('Group')
                    ->badge()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_public')
                    ->label('Public')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiteSettings::route('/'),
            'edit' => Pages\EditSiteSetting::route('/{record}/edit'),
        ];
    }
}
