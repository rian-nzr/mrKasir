<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentMethodTransactionResource\Pages;
use App\Models\PaymentMethodTransaction;
use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Model;

class PaymentMethodTransactionResource extends Resource
{
    protected static ?string $model = PaymentMethodTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = 'Riwayat Transaksi E-Wallet';
    
    protected static ?string $modelLabel = 'Transaksi E-Wallet';
    
    protected static ?string $pluralModelLabel = 'Riwayat Transaksi E-Wallet';
    
    protected static ?string $navigationGroup = 'Payment Methods';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form tidak diperlukan karena hanya view
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_method_name')
                    ->label('Payment Method')
                    ->getStateUsing(function (PaymentMethodTransaction $record) {
                        return $record->main_payment_method?->name ?? '-';
                    })
                    ->searchable()
                    ->sortable(false),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipe Transaksi')
                    ->color(fn (string $state): string => match ($state) {
                        'topup' => 'success',         // Hijau untuk Top Up
                        'transfer_in' => 'info',      // Biru untuk Transfer Masuk
                        'withdraw' => 'danger',       // Merah untuk Tarik Saldo
                        'transfer_out' => 'warning',  // Kuning untuk Transfer Keluar
                        'adjustment' => 'gray',       // Abu-abu untuk Penyesuaian
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match($state) {
                        'topup' => '💰 Top Up',
                        'withdraw' => '💸 Tarik Saldo', 
                        'transfer_in' => '📥 Transfer Masuk',
                        'transfer_out' => '📤 Transfer Keluar',
                        'adjustment' => '⚙️ Penyesuaian',
                        default => ucfirst($state)
                    }),
                Tables\Columns\TextColumn::make('formatted_amount')
                    ->label('Jumlah')
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderBy('amount', $direction);
                    }),
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('No. Referensi')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Nomor referensi disalin')
                    ->limit(20),
                Tables\Columns\TextColumn::make('formatted_balance_before')
                    ->label('Saldo Sebelum')
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderBy('balance_before', $direction);
                    }),
                Tables\Columns\TextColumn::make('formatted_balance_after')
                    ->label('Saldo Sesudah')
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderBy('balance_after', $direction);
                    }),
                Tables\Columns\TextColumn::make('from_payment_method.name')
                    ->label('Dari')
                    ->visible(fn ($record) => $record && $record->type === 'transfer_in')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('to_payment_method.name')
                    ->label('Ke')
                    ->visible(fn ($record) => $record && $record->type === 'transfer_out')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(30)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('payment_method_id')
                    ->label('Payment Method')
                    ->options(function () {
                        return \App\Models\PaymentMethod::pluck('name', 'id');
                    })
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            $query->where(function ($q) use ($data) {
                                $q->where('from_payment_method_id', $data['value'])
                                  ->orWhere('to_payment_method_id', $data['value']);
                            });
                        }
                    })
                    ->searchable()
                    ->preload(),
                SelectFilter::make('type')
                    ->label('Tipe Transaksi')
                    ->options([
                        'topup' => 'Top Up',
                        'withdraw' => 'Tarik Saldo',
                        'transfer_in' => 'Transfer Masuk',
                        'transfer_out' => 'Transfer Keluar',
                        'adjustment' => 'Penyesuaian',
                    ]),
                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                // Tidak ada action edit/delete karena transaksi tidak boleh diubah
            ])
            ->bulkActions([
                // Tidak ada bulk action karena transaksi tidak boleh dihapus
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                // Filter berdasarkan store yang dipilih user
                $selectedStore = session('selected_store_id');
                if ($selectedStore) {
                    $query->where(function ($q) use ($selectedStore) {
                        $q->whereHas('fromPaymentMethod', function (Builder $query) use ($selectedStore) {
                            $query->where('store_id', $selectedStore);
                        })->orWhereHas('toPaymentMethod', function (Builder $query) use ($selectedStore) {
                            $query->where('store_id', $selectedStore);
                        });
                    });
                }
                return $query;
            });
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentMethodTransactions::route('/'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false; // Transaksi tidak dapat dibuat manual
    }
    
    public static function canEdit(Model $record): bool
    {
        return false; // Transaksi tidak dapat diedit
    }
    
    public static function canDelete(Model $record): bool
    {
        return false; // Transaksi tidak dapat dihapus
    }
}
