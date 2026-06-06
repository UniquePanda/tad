<?php

namespace App\Filament\Resources\KnownReleases\Pages;

use App\Filament\Resources\KnownReleases\KnownReleaseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKnownRelease extends EditRecord
{
    protected static string $resource = KnownReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
