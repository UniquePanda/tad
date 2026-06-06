<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('username')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    // Hashing is handled by the model's 'password' => 'hashed' cast.
                    ->required(fn (string $operation): bool => $operation === 'create')
                    // Keep the existing password when the field is left blank on edit.
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                Toggle::make('is_admin')
                    ->label('Administrator'),
            ]);
    }
}
