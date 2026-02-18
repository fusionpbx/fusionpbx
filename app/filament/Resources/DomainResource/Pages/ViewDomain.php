<?php

namespace FusionPBX\Filament\Resources\DomainResource\Pages;

use FusionPBX\Filament\Resources\DomainResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDomain extends ViewRecord
{
    protected static string $resource = DomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
