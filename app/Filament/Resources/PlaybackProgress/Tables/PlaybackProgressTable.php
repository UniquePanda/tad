<?php

namespace App\Filament\Resources\PlaybackProgress\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlaybackProgressTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('show.name')
                    ->searchable(),
                TextColumn::make('spotify_album_id')
                    ->searchable(),
                TextColumn::make('context_uri')
                    ->searchable(),
                TextColumn::make('track_uri')
                    ->searchable(),
                TextColumn::make('track_name')
                    ->searchable(),
                TextColumn::make('position_ms')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('duration_ms')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
