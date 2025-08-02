<?php

namespace App\Filament\Resources\CashierShiftResource\Pages;

use App\Filament\Resources\CashierShiftResource;
use App\Models\CashierShift;
use App\Services\CashierShiftService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class ListCashierShifts extends ListRecords
{
    protected static string $resource = CashierShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('open_shift')
                ->label('Buka Shift Baru')
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn () => !CashierShift::getActiveShiftForUser(auth()->id()))
                ->form([
                    Forms\Components\TextInput::make('opening_cash')
                        ->label('Jumlah Kas Awal')
                        ->helperText('Masukkan jumlah uang tunai yang tersedia di laci kasir')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->step(1000)
                        ->minValue(0),
                    Forms\Components\TextInput::make('location')
                        ->label('Lokasi/Counter')
                        ->helperText('Pilih laci kasir atau lokasi kerja (opsional)')
                        ->maxLength(255),
                    Forms\Components\Textarea::make('notes')
                        ->label('Catatan Tambahan')
                        ->helperText('Tambahkan keterangan atau kondisi khusus (opsional)')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    try {
                        $service = app(CashierShiftService::class);
                        $shift = $service->openShift(
                            auth()->id(),
                            $data['opening_cash'],
                            $data['location'] ?? null,
                            $data['notes'] ?? null
                        );
                        
                        Notification::make()
                            ->title('Shift Berhasil Dibuka')
                            ->body("Shift {$shift->getFormattedShiftNumber()} telah dibuka dengan kas awal Rp " . number_format($data['opening_cash'], 0, ',', '.'))
                            ->success()
                            ->send();
                            
                        return redirect()->to(CashierShiftResource::getUrl('view', ['record' => $shift]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal Membuka Shift')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->modalWidth('lg'),

            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('create_cashier_shift')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\CashierShiftStatsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua'),
            
            'open' => Tab::make('Terbuka')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'open'))
                ->badge(fn () => CashierShift::where('status', 'open')->count())
                ->badgeColor('success'),
                
            'closed' => Tab::make('Ditutup')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'closed'))
                ->badge(fn () => CashierShift::where('status', 'closed')->count())
                ->badgeColor('gray'),
                
            'today' => Tab::make('Hari Ini')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('opened_at', today()))
                ->badge(fn () => CashierShift::whereDate('opened_at', today())->count())
                ->badgeColor('info'),
        ];
    }
}
