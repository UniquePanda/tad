<?php

namespace App\Filament\Resources\KnownReleases\Schemas;

use App\Enums\ReleaseDatePrecision;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class KnownReleaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('show_id')
                    ->relationship('show', 'name')
                    ->required(),
                TextInput::make('spotify_album_id')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('release_date')
                    ->required(),
                Select::make('release_date_precision')
                    ->options(ReleaseDatePrecision::class)
                    ->required(),
                TextInput::make('total_tracks')
                    ->numeric(),
                FileUpload::make('image_url')
                    ->image(),
                DateTimePicker::make('notified_at'),
            ]);
    }
}
