<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    protected function mutateRecordDataUsing(array $data): array
    {
        // Convert all array values to safe strings
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = json_encode($value, JSON_PRETTY_PRINT);
            } elseif (is_null($value)) {
                $data[$key] = '-';
            } elseif (is_bool($value)) {
                $data[$key] = $value ? 'Ya' : 'Tidak';
            } else {
                $data[$key] = (string) $value;
            }
        }
        
        return $data;
    }
}
