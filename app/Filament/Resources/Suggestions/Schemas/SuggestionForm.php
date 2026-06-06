<?php

namespace App\Filament\Resources\Suggestions\Schemas;

use App\Enums\SuggestionStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SuggestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'username')
                    ->required(),
                TextInput::make('spotify_artist_id'),
                TextInput::make('spotify_album_id'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('reason'),
                TextInput::make('score')
                    ->numeric(),
                Select::make('status')
                    ->options(SuggestionStatus::class)
                    ->default('open')
                    ->required(),
            ]);
    }
}
