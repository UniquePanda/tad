<?php

namespace App\Filament\Resources\SpotifyAuths\Pages;

use App\Filament\Resources\SpotifyAuths\SpotifyAuthResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSpotifyAuth extends EditRecord
{
    protected static string $resource = SpotifyAuthResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
