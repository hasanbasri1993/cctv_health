<?php

namespace App\Filament\Resources\CctvResource\Pages;

use App\Filament\Resources\CctvResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCctv extends ViewRecord
{
    protected static string $resource = CctvResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
