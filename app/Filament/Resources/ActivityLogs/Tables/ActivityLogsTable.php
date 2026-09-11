<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('Time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('User')
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : 'System')
                    ->sortable(),
                TextColumn::make('subject_id')
                    ->label('Subject ID')
                    ->sortable(),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('user_agent')
                    ->label('User agent')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->options([
                        'login' => 'Login',
                        'failed_login' => 'Failed login',
                        'article.create' => 'Article created',
                        'article.update' => 'Article updated',
                        'article.publish' => 'Article published',
                        'article.unpublish' => 'Article unpublished',
                        'article.archive' => 'Article archived',
                        'category.change' => 'Category changed',
                        'homepage.change' => 'Homepage changed',
                        'user.create' => 'User created',
                        'user.disable' => 'User disabled',
                        'role.change' => 'Role changed',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('occurred_at', 'desc');
    }
}
