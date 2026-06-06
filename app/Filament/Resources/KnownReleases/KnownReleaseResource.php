<?php

namespace App\Filament\Resources\KnownReleases;

use App\Filament\Resources\KnownReleases\Pages\CreateKnownRelease;
use App\Filament\Resources\KnownReleases\Pages\EditKnownRelease;
use App\Filament\Resources\KnownReleases\Pages\ListKnownReleases;
use App\Filament\Resources\KnownReleases\Schemas\KnownReleaseForm;
use App\Filament\Resources\KnownReleases\Tables\KnownReleasesTable;
use App\Models\KnownRelease;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class KnownReleaseResource extends Resource
{
    protected static ?string $model = KnownRelease::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return KnownReleaseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KnownReleasesTable::configure($table);
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
            'index' => ListKnownReleases::route('/'),
            'create' => CreateKnownRelease::route('/create'),
            'edit' => EditKnownRelease::route('/{record}/edit'),
        ];
    }
}
