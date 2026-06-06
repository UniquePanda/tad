<?php

namespace App\Filament\Resources\PlaybackProgress;

use App\Filament\Resources\PlaybackProgress\Pages\CreatePlaybackProgress;
use App\Filament\Resources\PlaybackProgress\Pages\EditPlaybackProgress;
use App\Filament\Resources\PlaybackProgress\Pages\ListPlaybackProgress;
use App\Filament\Resources\PlaybackProgress\Schemas\PlaybackProgressForm;
use App\Filament\Resources\PlaybackProgress\Tables\PlaybackProgressTable;
use App\Models\PlaybackProgress;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PlaybackProgressResource extends Resource
{
    protected static ?string $model = PlaybackProgress::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PlaybackProgressForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlaybackProgressTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlaybackProgress::route('/'),
            'create' => CreatePlaybackProgress::route('/create'),
            'edit' => EditPlaybackProgress::route('/{record}/edit'),
        ];
    }
}
