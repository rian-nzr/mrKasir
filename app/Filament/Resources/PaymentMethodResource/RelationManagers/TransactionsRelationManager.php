<?php

namespace App\Filament\Resources\PaymentMethodResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Riwayat Transaksi';
    
    protected static ?string $label = 'Transaksi';
    
    protected static ?string $pluralLabel = 'Transaksi';

    public function getTableQuery(): Builder
    {
        return $this->getOwnerRecord()->allTransactions();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Tidak ada form karena transaksi tidak bisa diedit
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference_number')
            ->columns([
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipe')
                    ->colors([
                        'success' => ['topup', 'transfer_in'],
                        'danger' => ['withdraw', 'transfer_out'],
                        'warning' => ['adjustment'],
                    ])
                    ->formatStateUsing(fn (string $state) => match($state) {
                        'topup' => 'Top Up',
                        'withdraw' => 'Tarik Saldo',
                        'transfer_in' => 'Transfer Masuk',
                        'transfer_out' => 'Transfer Keluar',
                        'adjustment' => 'Penyesuaian',
                        default => ucfirst($state)
                    }),
                Tables\Columns\TextColumn::make('formatted_amount')
                    ->label('Jumlah')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('No. Referensi')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Nomor referensi disalin')
                    ->limit(15),
                Tables\Columns\TextColumn::make('formatted_balance_before')
                    ->label('Saldo Sebelum'),
                Tables\Columns\TextColumn::make('formatted_balance_after')
                    ->label('Saldo Sesudah'),
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
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Dibuat Oleh')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe Transaksi')
                    ->options([
                        'topup' => 'Top Up',
                        'withdraw' => 'Tarik Saldo',
                        'transfer_in' => 'Transfer Masuk',
                        'transfer_out' => 'Transfer Keluar',
                        'adjustment' => 'Penyesuaian',
                    ]),
            ])
            ->headerActions([
                // Tidak ada action create
            ])
            ->actions([
                // Tidak ada action edit/delete
            ])
            ->bulkActions([
                // Tidak ada bulk action
            ])
            ->defaultSort('created_at', 'desc');
    }
}
