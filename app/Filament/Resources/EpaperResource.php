<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EpaperResource\Pages;
use App\Models\Epaper;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class EpaperResource extends Resource
{
    protected static ?string $model = Epaper::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'E-Papers';

    protected static ?string $modelLabel = 'E-paper';

    protected static ?string $pluralModelLabel = 'E-Papers';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $slug = 'epapers';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Issue details')
                    ->schema([
                        TextInput::make('title_bn')
                            ->label('Bangla title')
                            ->required()
                            ->maxLength(255),
                        DatePicker::make('issue_date')
                            ->label('Issue date')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->native(false),
                        TextInput::make('edition')
                            ->label('Edition')
                            ->default('ঢাকা')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('sort_order')
                            ->label('Sort order')
                            ->numeric()
                            ->minValue(0)
                            ->default(100)
                            ->required(),
                        Toggle::make('is_published')
                            ->label('Published')
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Uploaded files and links')
                    ->description('These legacy fields are used as page 1 when no page list is added below.')
                    ->schema([
                        FileUpload::make('scan_path')
                            ->label('Scanned page image')
                            ->image()
                            ->disk('public')
                            ->directory('epapers')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(12288),
                        FileUpload::make('pdf_path')
                            ->label('PDF file')
                            ->disk('public')
                            ->directory('epapers')
                            ->visibility('public')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(30720),
                        TextInput::make('external_url')
                            ->label('External e-paper / Google Drive link')
                            ->helperText('Paste a Google Drive share link, PDF link, or e-paper viewer URL.')
                            ->url()
                            ->maxLength(2048)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Issue pages')
                    ->description('Paste up to 8 page links for this same issue date.')
                    ->schema([
                        Repeater::make('pages')
                            ->label('Page links')
                            ->schema([
                                Select::make('page_number')
                                    ->label('Page number')
                                    ->options([
                                        1 => 'Page 1',
                                        2 => 'Page 2',
                                        3 => 'Page 3',
                                        4 => 'Page 4',
                                        5 => 'Page 5',
                                        6 => 'Page 6',
                                        7 => 'Page 7',
                                        8 => 'Page 8',
                                    ])
                                    ->required()
                                    ->native(false),
                                TextInput::make('title')
                                    ->label('Page title')
                                    ->placeholder('Enter page title')
                                    ->maxLength(255),
                                FileUpload::make('scan_path')
                                    ->label('Page image')
                                    ->image()
                                    ->disk('public')
                                    ->directory('epapers')
                                    ->visibility('public')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(12288),
                                TextInput::make('external_url')
                                    ->label('Page link (URL)')
                                    ->placeholder('https://example.com/page-link')
                                    ->url()
                                    ->maxLength(2048),
                            ])
                            ->itemLabel(fn (array $state): string => 'Page '.($state['page_number'] ?? ''))
                            ->default([
                                ['page_number' => 1, 'title' => 'Page 1'],
                                ['page_number' => 2, 'title' => 'Page 2'],
                                ['page_number' => 3, 'title' => 'Page 3'],
                                ['page_number' => 4, 'title' => 'Page 4'],
                                ['page_number' => 5, 'title' => 'Page 5'],
                                ['page_number' => 6, 'title' => 'Page 6'],
                                ['page_number' => 7, 'title' => 'Page 7'],
                                ['page_number' => 8, 'title' => 'Page 8'],
                            ])
                            ->maxItems(8)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columns([
                                'default' => 1,
                                'lg' => 4,
                            ])
                            ->extraAttributes(['class' => 'epaper-pages-repeater'])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('scan_path')
                    ->label('Scan')
                    ->disk('public')
                    ->square(),
                TextColumn::make('title_bn')
                    ->label('Title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('issue_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('edition')
                    ->label('Edition')
                    ->searchable(),
                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('issue_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEpapers::route('/'),
            'create' => Pages\CreateEpaper::route('/create'),
            'edit' => Pages\EditEpaper::route('/{record}/edit'),
        ];
    }
}
