<?php

namespace App\Filament\Resources\CctvResource\Pages;

use App\Filament\Resources\CctvResource;
use App\Filament\Resources\CctvResource\Widgets\TemperatureChart;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class ViewCctv extends ViewRecord
{
    use InteractsWithPageTable;

    protected static string $resource = CctvResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TemperatureChart::class,
        ];
    }
    
    public function getWidgetData(): array
    {
        return [
            'record' => $this->getRecord(),
        ];
    }

}
