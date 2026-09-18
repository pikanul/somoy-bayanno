<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CareerApplicationResource\Pages;
use App\Models\CareerApplication;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
use UnitEnum;

class CareerApplicationResource extends Resource
{
    protected static ?string $model = CareerApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Applications';

    protected static ?string $modelLabel = 'Career Application';

    protected static ?string $pluralModelLabel = 'Career Applications';

    protected static string|UnitEnum|null $navigationGroup = 'Career';

    protected static ?string $slug = 'career-applications';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Applicant')
                ->schema([
                    TextInput::make('full_name')->disabled(),
                    TextInput::make('email')->disabled(),
                    TextInput::make('phone')->disabled(),
                    TextInput::make('position')->disabled(),
                    TextInput::make('location')->disabled(),
                    TextInput::make('portfolio_url')->disabled(),
                    TextInput::make('linkedin_url')->disabled(),
                    TextInput::make('cv_path')->label('Private CV path')->disabled(),
                    Textarea::make('cover_letter')->rows(6)->disabled()->columnSpanFull(),
                    Select::make('status')->options([
                        'new' => 'New',
                        'reviewing' => 'Reviewing',
                        'shortlisted' => 'Shortlisted',
                        'rejected' => 'Rejected',
                        'hired' => 'Hired',
                    ])->default('new')->required()->native(false),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('position')->searchable()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'new' => 'New',
                    'reviewing' => 'Reviewing',
                    'shortlisted' => 'Shortlisted',
                    'rejected' => 'Rejected',
                    'hired' => 'Hired',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCareerApplications::route('/'),
            'view' => Pages\ViewCareerApplication::route('/{record}'),
            'edit' => Pages\EditCareerApplication::route('/{record}/edit'),
        ];
    }
}
