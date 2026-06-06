<?php

namespace App\Filament\Resources\PlaybackProgress\Pages;

use App\Filament\Resources\PlaybackProgress\PlaybackProgressResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlaybackProgress extends ListRecords
{
    protected static string $resource = PlaybackProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
