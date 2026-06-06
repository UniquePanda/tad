<?php

namespace App\Filament\Resources\KnownReleases\Pages;

use App\Filament\Resources\KnownReleases\KnownReleaseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKnownReleases extends ListRecords
{
    protected static string $resource = KnownReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
