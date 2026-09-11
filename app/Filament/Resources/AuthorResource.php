<?php

namespace App\Filament\Resources;

use App\Enums\AuthorStatus;
use App\Filament\Resources\AuthorResource\Pages;
use App\Models\Author;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AuthorResource extends Resource
{
    protected static ?string $model = Author::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $navigationLabel = 'Authors';

    protected static ?string $modelLabel = 'Author';

    protected static ?string $pluralModelLabel = 'Authors';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $slug = 'authors';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Linked CMS user')
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->native(false),
                TextInput::make('name_bn')
                    ->label('Bangla name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('name_en')
                    ->label('English name')
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->alphaDash()
                    ->unique(ignoreRecord: true),
                TextInput::make('designation')
                    ->label('Designation')
                    ->maxLength(255),
                FileUpload::make('photo')
                    ->label('Photo')
                    ->image()
                    ->disk('public')
                    ->directory('authors')
                    ->visibility('public'),
                Textarea::make('bio_bn')
                    ->label('Bangla bio')
                    ->columnSpanFull(),
                Textarea::make('bio_en')
                    ->label('English bio')
                    ->columnSpanFull(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('Phone')
                    ->tel()
                    ->maxLength(50),
                TextInput::make('facebook_url')
                    ->label('Facebook URL')
                    ->url()
                    ->maxLength(2048),
                TextInput::make('x_url')
                    ->label('X URL')
                    ->url()
                    ->maxLength(2048),
                TextInput::make('linkedin_url')
                    ->label('LinkedIn URL')
                    ->url()
                    ->maxLength(2048),
                TextInput::make('website_url')
                    ->label('Website URL')
                    ->url()
                    ->maxLength(2048),
                Select::make('status')
                    ->label('Status')
                    ->options(AuthorStatus::options())
                    ->default(AuthorStatus::Active->value)
                    ->required()
                    ->native(false),
                Toggle::make('featured')
                    ->label('Featured')
                    ->default(false),
                TextInput::make('seo_title')
                    ->label('SEO title')
                    ->maxLength(255),
                Textarea::make('seo_description')
                    ->label('SEO description')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo')
                    ->label('Photo')
                    ->disk('public')
                    ->circular(),
                TextColumn::make('name_bn')
                    ->label('Bangla name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name_en')
                    ->label('English name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('designation')
                    ->label('Designation')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label('CMS user')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('featured')
                    ->label('Featured')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (AuthorStatus|string|null $state): string => $state instanceof AuthorStatus ? $state->label() : ucfirst((string) $state))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(AuthorStatus::options()),
                TernaryFilter::make('featured'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->defaultSort('name_bn');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuthors::route('/'),
            'create' => Pages\CreateAuthor::route('/create'),
            'edit' => Pages\EditAuthor::route('/{record}/edit'),
        ];
    }
}
