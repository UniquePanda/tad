<?php

namespace App\Filament\Resources\Shows\Schemas;

use App\Enums\ShowSource;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ShowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'username')
                    ->required(),
                TextInput::make('spotify_artist_id')
                    ->required(),
                TextInput::make('series_key'),
                TextInput::make('name')
                    ->required(),
                FileUpload::make('image_url')
                    ->image(),
                Select::make('source')
                    ->options(ShowSource::class)
                    ->required(),
                DateTimePicker::make('last_checked_at'),
            ]);
    }
}
