<?php

namespace FusionPBX\Filament\Resources\ExtensionResource\Pages;

use FusionPBX\Filament\Resources\ExtensionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewExtension extends ViewRecord
{
    protected static string $resource = ExtensionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
