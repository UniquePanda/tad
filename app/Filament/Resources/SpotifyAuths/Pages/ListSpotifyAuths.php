<?php

namespace App\Filament\Resources\SpotifyAuths\Pages;

use App\Filament\Resources\SpotifyAuths\SpotifyAuthResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSpotifyAuths extends ListRecords
{
    protected static string $resource = SpotifyAuthResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
