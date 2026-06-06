<?php

namespace App\Filament\Resources\SpotifyAuths\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SpotifyAuthForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'id')
                    ->required(),
                TextInput::make('spotify_user_id'),
                DateTimePicker::make('access_token_expires_at'),
                TextInput::make('scope'),
            ]);
    }
}
