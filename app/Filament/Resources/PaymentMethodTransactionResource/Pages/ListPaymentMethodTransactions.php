<?php

namespace App\Filament\Resources\PaymentMethodTransactionResource\Pages;

use App\Filament\Resources\PaymentMethodTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentMethodTransactions extends ListRecords
{
    protected static string $resource = PaymentMethodTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tidak ada action create karena transaksi tidak dapat dibuat manual
        ];
    }
    
    public function getTitle(): string
    {
        return 'Riwayat Transaksi E-Wallet';
    }
}
