<?php

namespace App\Filament\Resources\SpotifyAuths;

use App\Filament\Resources\SpotifyAuths\Pages\CreateSpotifyAuth;
use App\Filament\Resources\SpotifyAuths\Pages\EditSpotifyAuth;
use App\Filament\Resources\SpotifyAuths\Pages\ListSpotifyAuths;
use App\Filament\Resources\SpotifyAuths\Schemas\SpotifyAuthForm;
use App\Filament\Resources\SpotifyAuths\Tables\SpotifyAuthsTable;
use App\Models\SpotifyAuth;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SpotifyAuthResource extends Resource
{
    protected static ?string $model = SpotifyAuth::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SpotifyAuthForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SpotifyAuthsTable::configure($table);
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
            'index' => ListSpotifyAuths::route('/'),
            'create' => CreateSpotifyAuth::route('/create'),
            'edit' => EditSpotifyAuth::route('/{record}/edit'),
        ];
    }
}
