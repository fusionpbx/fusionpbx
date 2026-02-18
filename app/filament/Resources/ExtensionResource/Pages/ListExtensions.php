<?php

namespace FusionPBX\Filament\Resources\ExtensionResource\Pages;

use FusionPBX\Filament\Resources\ExtensionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExtensions extends ListRecords
{
    protected static string $resource = ExtensionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
