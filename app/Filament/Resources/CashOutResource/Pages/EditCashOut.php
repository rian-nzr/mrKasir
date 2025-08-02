<?php

namespace App\Filament\Resources\CashOutResource\Pages;

use App\Filament\Resources\CashOutResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditCashOut extends EditRecord
{
    protected static string $resource = CashOutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status === 'pending'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Convert decimal values for display
        $data['amount'] = (float) $data['amount'];
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Only allow editing approval fields if user has permission
        if (!auth()->user()->can('approve_cash_out')) {
            unset($data['status'], $data['approval_notes']);
        }

        // Don't allow editing basic fields for non-pending records
        if ($this->record->status !== 'pending') {
            $data = array_intersect_key($data, array_flip([
                'status',
                'approval_notes'
            ]));
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $message = match($this->record->status) {
            'approved' => 'Pengeluaran kas berhasil disetujui',
            'rejected' => 'Pengeluaran kas telah ditolak',
            default => 'Pengeluaran kas berhasil diperbarui'
        };
        
        Notification::make()
            ->title('Berhasil Diperbarui')
            ->body($message)
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
