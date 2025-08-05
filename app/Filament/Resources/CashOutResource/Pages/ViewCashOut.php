<?php

namespace App\Filament\Resources\CashOutResource\Pages;

use App\Filament\Resources\CashOutResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;

class ViewCashOut extends ViewRecord
{
    protected static string $resource = CashOutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => $this->record->status === 'pending'),

            Actions\Action::make('approve')
                ->label('Setujui')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === 'pending' && auth()->user()->can('approve_cash_out'))
                ->form([
                    Forms\Components\Textarea::make('approval_notes')
                        ->label('Catatan Persetujuan')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    try {
                        $this->record->approve(auth()->id(), $data['approval_notes'] ?? null);
                        
                        Notification::make()
                            ->title('Pengeluaran Disetujui')
                            ->body('Pengeluaran kas berhasil disetujui')
                            ->success()
                            ->send();
                            
                        $this->refreshFormData(['status', 'approved_at', 'approval_notes']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal Menyetujui')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation(),

            Actions\Action::make('reject')
                ->label('Tolak')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'pending' && auth()->user()->can('approve_cash_out'))
                ->form([
                    Forms\Components\Textarea::make('approval_notes')
                        ->label('Alasan Penolakan')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    try {
                        $this->record->reject(auth()->id(), $data['approval_notes']);
                        
                        Notification::make()
                            ->title('Pengeluaran Ditolak')
                            ->body('Pengeluaran kas telah ditolak')
                            ->warning()
                            ->send();
                            
                        $this->refreshFormData(['status', 'approved_at', 'approval_notes']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal Menolak')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Pengeluaran')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('cashierShift.shift_number')
                                    ->label('Nomor Shift')
                                    ->weight(FontWeight::Bold)
                                    ->icon('heroicon-o-calculator'),
                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('Kasir')
                                    ->icon('heroicon-o-user'),
                                Infolists\Components\TextEntry::make('amount')
                                    ->label('Jumlah')
                                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                                    ->weight(FontWeight::Bold)
                                    ->color('danger')
                                    ->icon('heroicon-o-banknotes'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'pending' => 'Menunggu Persetujuan',
                                        'approved' => 'Disetujui',
                                        'rejected' => 'Ditolak',
                                        default => $state,
                                    }),
                            ]),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Detail Pengeluaran')
                    ->schema([
                        Infolists\Components\TextEntry::make('reason')
                            ->label('Alasan')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Catatan')
                            ->placeholder('Tidak ada catatan')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Informasi Persetujuan')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('approvedBy.name')
                                    ->label('Disetujui Oleh')
                                    ->placeholder('Belum disetujui')
                                    ->icon('heroicon-o-user'),
                                Infolists\Components\TextEntry::make('approved_at')
                                    ->label('Waktu Persetujuan')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->placeholder('Belum disetujui')
                                    ->icon('heroicon-o-clock'),
                            ]),
                        Infolists\Components\TextEntry::make('approval_notes')
                            ->label('Catatan Persetujuan')
                            ->placeholder('Tidak ada catatan')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->approved_at || $record->approval_notes)
                    ->collapsible(),

                Infolists\Components\Section::make('Informasi Waktu')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Waktu Pengajuan')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->icon('heroicon-o-clock'),
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Terakhir Diperbarui')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->icon('heroicon-o-arrow-path'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
