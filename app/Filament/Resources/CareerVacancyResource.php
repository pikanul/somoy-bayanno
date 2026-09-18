<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CareerVacancyResource\Pages;
use App\Models\CareerVacancy;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
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
use UnitEnum;

class CareerVacancyResource extends Resource
{
    protected static ?string $model = CareerVacancy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $navigationLabel = 'Job Vacancies';

    protected static ?string $modelLabel = 'Job Vacancy';

    protected static ?string $pluralModelLabel = 'Job Vacancies';

    protected static string|UnitEnum|null $navigationGroup = 'Career';

    protected static ?string $slug = 'career-vacancies';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Vacancy')
                ->schema([
                    TextInput::make('title')->required()->maxLength(255),
                    TextInput::make('slug')->required()->alphaDash()->unique(ignoreRecord: true)->maxLength(255),
                    TextInput::make('department')->required()->maxLength(255),
                    TextInput::make('location')->required()->maxLength(255),
                    TextInput::make('employment_type')->required()->default('Full-time')->maxLength(120),
                    Select::make('status')->options([
                        'draft' => 'Draft',
                        'open' => 'Open',
                        'closing_soon' => 'Closing Soon',
                        'closed' => 'Closed',
                    ])->default('draft')->required()->native(false),
                    DateTimePicker::make('published_at')->seconds(false),
                    DatePicker::make('application_deadline'),
                    TextInput::make('sort_order')->numeric()->default(100)->required(),
                    TextInput::make('application_email')->email()->maxLength(255),
                ])->columns(2),
            Section::make('Details')
                ->schema([
                    Textarea::make('summary')->rows(3)->columnSpanFull(),
                    Textarea::make('responsibilities')->rows(4)->columnSpanFull(),
                    Textarea::make('requirements')->rows(4)->columnSpanFull(),
                    Textarea::make('qualifications')->rows(3)->columnSpanFull(),
                    TextInput::make('experience')->maxLength(255),
                    Textarea::make('skills')->rows(3),
                    Textarea::make('salary_benefits')->rows(3),
                    Textarea::make('application_instructions')->rows(3),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('department')->searchable()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('application_deadline')->date()->sortable(),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'open' => 'Open',
                    'closing_soon' => 'Closing Soon',
                    'closed' => 'Closed',
                ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCareerVacancies::route('/'),
            'create' => Pages\CreateCareerVacancy::route('/create'),
            'edit' => Pages\EditCareerVacancy::route('/{record}/edit'),
        ];
    }
}
