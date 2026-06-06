<?php

namespace App\Filament\Resources\KnownReleases\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KnownReleasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('show.name')
                    ->searchable(),
                TextColumn::make('spotify_album_id')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('release_date')
                    ->searchable(),
                TextColumn::make('release_date_precision')
                    ->badge()
                    ->searchable(),
                TextColumn::make('total_tracks')
                    ->numeric()
                    ->sortable(),
                ImageColumn::make('image_url'),
                TextColumn::make('notified_at')
                    ->dateTime()
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
