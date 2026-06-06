<?php

namespace App\Filament\Resources\PlaybackProgress\Pages;

use App\Filament\Resources\PlaybackProgress\PlaybackProgressResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPlaybackProgress extends EditRecord
{
    protected static string $resource = PlaybackProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
