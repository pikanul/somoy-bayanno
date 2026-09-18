<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvertisementInquiryResource\Pages;
use App\Models\AdvertisementInquiry;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
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

class AdvertisementInquiryResource extends Resource
{
    protected static ?string $model = AdvertisementInquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Advertisement Inquiries';

    protected static ?string $modelLabel = 'Advertisement Inquiry';

    protected static ?string $pluralModelLabel = 'Advertisement Inquiries';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $slug = 'advertisement-inquiries';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contact')
                    ->schema([
                        TextInput::make('name')->disabled(),
                        TextInput::make('company_name')->label('Company')->disabled(),
                        TextInput::make('phone')->disabled(),
                        TextInput::make('email')->disabled(),
                    ])
                    ->columns(2),
                Section::make('Advertisement')
                    ->schema([
                        TextInput::make('advertisement_type')->label('Type')->disabled(),
                        TextInput::make('placement')->disabled(),
                        TextInput::make('budget')->disabled(),
                        TextInput::make('landing_page_url')->label('Landing page URL')->disabled(),
                        DatePicker::make('starts_on')->disabled(),
                        DatePicker::make('ends_on')->disabled(),
                        Textarea::make('message')->rows(5)->disabled()->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('advertisement_type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('placement')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('advertisement_type')
                    ->label('Type')
                    ->options([
                        'top-banner' => 'Top Banner',
                        'desktop-banner' => 'Desktop Banner',
                        'sidebar-banner' => 'Sidebar Banner',
                        'mobile-banner' => 'Mobile Banner',
                        'sponsored-content' => 'Sponsored Content',
                        'video-advertisement' => 'Video Advertisement',
                        'brand-campaign' => 'Brand Campaign',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdvertisementInquiries::route('/'),
            'view' => Pages\ViewAdvertisementInquiry::route('/{record}'),
        ];
    }
}
