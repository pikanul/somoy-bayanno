<?php

namespace App\Filament\Resources\ActivityLogs\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('occurred_at')
                    ->label('Time')
                    ->dateTime(),
                TextEntry::make('action')
                    ->label('Action')
                    ->badge(),
                TextEntry::make('user.email')
                    ->label('User'),
                TextEntry::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : 'System'),
                TextEntry::make('subject_id')
                    ->label('Subject ID'),
                TextEntry::make('ip_address')
                    ->label('IP address'),
                TextEntry::make('user_agent')
                    ->label('User agent')
                    ->columnSpanFull(),
                KeyValueEntry::make('old_values')
                    ->label('Old values')
                    ->columnSpanFull(),
                KeyValueEntry::make('new_values')
                    ->label('New values')
                    ->columnSpanFull(),
                KeyValueEntry::make('properties')
                    ->label('Properties')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
