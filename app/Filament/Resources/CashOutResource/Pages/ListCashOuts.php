<?php

namespace App\Filament\Resources\CashOutResource\Pages;

use App\Filament\Resources\CashOutResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCashOuts extends ListRecords
{
    protected static string $resource = CashOutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Pengeluaran')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(fn () => $this->getResource()::getEloquentQuery()->count()),

            'pending' => Tab::make('Menunggu Persetujuan')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => $this->getResource()::getEloquentQuery()->where('status', 'pending')->count())
                ->badgeColor('warning'),

            'approved' => Tab::make('Disetujui')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved'))
                ->badge(fn () => $this->getResource()::getEloquentQuery()->where('status', 'approved')->count())
                ->badgeColor('success'),

            'rejected' => Tab::make('Ditolak')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected'))
                ->badge(fn () => $this->getResource()::getEloquentQuery()->where('status', 'rejected')->count())
                ->badgeColor('danger'),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'pending';
    }
}
