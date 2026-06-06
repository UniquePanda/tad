<?php

namespace App\Filament\Resources\PlaybackProgress\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PlaybackProgressForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'username')
                    ->required(),
                Select::make('show_id')
                    ->relationship('show', 'name'),
                TextInput::make('spotify_album_id')
                    ->required(),
                TextInput::make('context_uri'),
                TextInput::make('track_uri')
                    ->required(),
                TextInput::make('track_name'),
                TextInput::make('position_ms')
                    ->required()
                    ->numeric(),
                TextInput::make('duration_ms')
                    ->numeric(),
            ]);
    }
}
