<?php

namespace App\Filament\Resources;

use App\Enums\UserStatus;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Services\UserAdministrationService;
use App\Support\Security\Rbac;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Users';

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $slug = 'users';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->rule(Password::default()),
                Select::make('status')
                    ->label('Status')
                    ->options(UserStatus::options())
                    ->default(UserStatus::Active->value)
                    ->required()
                    ->native(false),
                Select::make('role')
                    ->label('Role')
                    ->options(fn (): array => Role::query()
                        ->whereIn('name', Rbac::roles())
                        ->orderBy('name')
                        ->pluck('name', 'name')
                        ->all())
                    ->searchable()
                    ->native(false)
                    ->visible(fn (): bool => Auth::user()?->can('role.manage') ?? false)
                    ->dehydrated(false),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (UserStatus|string|null $state): string => $state instanceof UserStatus ? $state->label() : ucfirst((string) $state))
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge(),
                TextColumn::make('two_factor')
                    ->label('2FA')
                    ->badge()
                    ->state(fn (User $record): string => $record->hasTwoFactorAuthenticationEnabled() ? 'Enabled' : 'Not set')
                    ->color(fn (string $state): string => $state === 'Enabled' ? 'success' : 'warning'),
                TextColumn::make('last_login_at')
                    ->label('Last login')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('resetTwoFactorAuthentication')
                    ->label('Reset 2FA')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reset two-factor authentication')
                    ->modalDescription('This clears the authenticator app secret and recovery-code hashes for this account. The user must enroll again before privileged access continues.')
                    ->visible(fn (User $record): bool => (Auth::user()?->can('update', $record) ?? false) && $record->hasTwoFactorAuthenticationEnabled())
                    ->action(function (User $record): void {
                        app(UserAdministrationService::class)->resetTwoFactorAuthentication(Auth::user(), $record);

                        Notification::make()
                            ->title('Two-factor authentication reset')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
