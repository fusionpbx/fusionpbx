<?php

namespace FusionPBX\Filament\Resources\ExtensionResource\Pages;

use FusionPBX\Filament\Resources\ExtensionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExtension extends EditRecord
{
    protected static string $resource = ExtensionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
