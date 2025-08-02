<?php

namespace App\Filament\Resources\CashOutResource\Pages;

use App\Filament\Resources\CashOutResource;
use App\Models\CashierShift;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateCashOut extends CreateRecord
{
    protected static string $resource = CashOutResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure the cashier shift is still open
        $shift = CashierShift::find($data['cashier_shift_id']);
        
        if (!$shift || !$shift->isOpen()) {
            Notification::make()
                ->title('Shift Tidak Valid')
                ->body('Pengeluaran kas hanya dapat dilakukan pada shift yang sedang aktif.')
                ->danger()
                ->send();
            
            $this->halt();
        }

        // Set default values
        $data['status'] = 'pending';
        $data['user_id'] = auth()->id();

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Pengeluaran kas berhasil dicatat dan menunggu persetujuan';
    }
}
