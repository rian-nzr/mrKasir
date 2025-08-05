<?php

namespace App\Filament\Resources\CashierShiftResource\Pages;

use App\Filament\Resources\CashierShiftResource;
use App\Services\CashierShiftService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateCashierShift extends CreateRecord
{
    protected static string $resource = CashierShiftResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = $data['user_id'] ?? auth()->id();
        $data['store_id'] = session('selected_store_id') ?? auth()->user()->store_id;
        $data['opened_at'] = now();
        
        return $data;
    }

    protected function afterCreate(): void
    {
        $shift = $this->record;
        
        if ($shift->status === 'open') {
            try {
                $service = app(CashierShiftService::class);
                $shift->open(
                    $shift->opening_cash,
                    $shift->location,
                    $shift->notes
                );
                
                Notification::make()
                    ->title('Shift Berhasil Dibuka')
                    ->body("Shift {$shift->getFormattedShiftNumber()} telah dibuka")
                    ->success()
                    ->send();
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Peringatan')
                    ->body('Shift dibuat tetapi gagal dibuka otomatis: ' . $e->getMessage())
                    ->warning()
                    ->send();
            }
        }
    }
}
