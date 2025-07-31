<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Transaction;
use App\Models\PaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\KeyValueEntry;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationLabel = 'Transaksi';
    
    protected static ?string $modelLabel = 'Transaksi';
    
    protected static ?string $pluralModelLabel = 'Transaksi';
    
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Jenis Transaksi')
                            ->options([
                                'transfer' => 'Transfer',
                                'tarik_tunai' => 'Tarik Tunai',
                                'jasa_transfer' => 'Jasa Transfer',
                                'mode_pulsa' => 'Mode Pulsa',
                            ])
                            ->required(),
                            
                        Forms\Components\Select::make('sumber_dana_id')
                            ->label('Sumber Dana')
                            ->relationship('sumberDana', 'name')
                            ->searchable()
                            ->preload(),
                            
                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah')
                            ->numeric()
                            ->prefix('Rp'),
                            
                        Forms\Components\TextInput::make('admin_luar')
                            ->label('Admin Luar')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                            
                        Forms\Components\TextInput::make('admin_dalam')
                            ->label('Admin Dalam')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                            
                        Forms\Components\TextInput::make('tujuan')
                            ->label('Tujuan')
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('terima_dana')
                            ->label('Terima Dana')
                            ->numeric()
                            ->prefix('Rp'),
                            
                        Forms\Components\TextInput::make('admin')
                            ->label('Admin')
                            ->numeric()
                            ->prefix('Rp'),
                            
                        Forms\Components\TextInput::make('jenis_transaksi')
                            ->label('Jenis Transaksi')
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('sumber')
                            ->label('Sumber')
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('modal')
                            ->label('Modal')
                            ->numeric()
                            ->prefix('Rp'),
                            
                        Forms\Components\TextInput::make('harga_jual')
                            ->label('Harga Jual')
                            ->numeric()
                            ->prefix('Rp'),
                            
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('completed'),
                            
                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(3),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                TextColumn::make('type_display')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Transfer' => 'info',
                        'Tarik Tunai' => 'danger',
                        'Jasa Transfer' => 'success',
                        'Mode Pulsa' => 'warning',
                        default => 'gray',
                    }),
                    
                TextColumn::make('display_amount')
                    ->label('Nominal')
                    ->weight(FontWeight::Bold),
                    
                TextColumn::make('sumberDana.name')
                    ->label('Sumber Dana')
                    ->default('-'),
                    
                TextColumn::make('total_profit')
                    ->label('Keuntungan')
                    ->money('IDR')
                    ->color('success'),
                    
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    }),
                    
                TextColumn::make('user.name')
                    ->label('Petugas')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis Transaksi')
                    ->options([
                        'transfer' => 'Transfer',
                        'tarik_tunai' => 'Tarik Tunai',
                        'jasa_transfer' => 'Jasa Transfer',
                        'mode_pulsa' => 'Mode Pulsa',
                    ]),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                    
                Tables\Filters\Filter::make('created_at')
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Transaksi')
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID Transaksi'),
                        TextEntry::make('type')
                            ->label('Jenis Transaksi')
                            ->formatStateUsing(function ($state) {
                                return match($state) {
                                    'transfer' => 'Transfer',
                                    'tarik_tunai' => 'Tarik Tunai',
                                    'jasa_transfer' => 'Jasa Transfer',
                                    'mode_pulsa' => 'Mode Pulsa',
                                    default => ucfirst($state)
                                };
                            }),
                        TextEntry::make('amount')
                            ->label('Nominal')
                            ->money('IDR'),
                        TextEntry::make('admin_dalam')
                            ->label('Admin Dalam')
                            ->money('IDR'),
                        TextEntry::make('admin_luar')
                            ->label('Admin Luar')
                            ->money('IDR'),
                        TextEntry::make('user.name')
                            ->label('User')
                            ->default('-'),
                        TextEntry::make('sumberDana.name')
                            ->label('Sumber Dana')
                            ->default('-'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'completed' => 'success',
                                'pending' => 'warning',
                                'failed' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('created_at')
                            ->label('Tanggal Transaksi')
                            ->dateTime(),
                    ]),
                
                Section::make('Detail Transaksi')
                    ->schema([
                        TextEntry::make('keterangan')
                            ->label('Keterangan')
                            ->default('-'),
                        TextEntry::make('tujuan')
                            ->label('Tujuan')
                            ->default('-'),
                        TextEntry::make('terima_dana')
                            ->label('Terima Dana')
                            ->default('-'),
                        TextEntry::make('jenis_transaksi')
                            ->label('Jenis Transaksi')
                            ->default('-'),
                        TextEntry::make('sumber')
                            ->label('Sumber')
                            ->default('-'),
                        TextEntry::make('modal')
                            ->label('Modal')
                            ->money('IDR')
                            ->default(0),
                        TextEntry::make('harga_jual')
                            ->label('Harga Jual')
                            ->money('IDR')
                            ->default(0),
                    ]),
                
                Section::make('Dampak Keuangan')
                    ->schema([
                        TextEntry::make('total_profit')
                            ->label('Total Keuntungan')
                            ->money('IDR')
                            ->formatStateUsing(function ($record) {
                                try {
                                    if (!$record || !isset($record->financial_impact)) {
                                        return 0;
                                    }
                                    
                                    $financialImpact = $record->financial_impact;
                                    
                                    // Handle string JSON
                                    if (is_string($financialImpact)) {
                                        $financialImpact = json_decode($financialImpact, true);
                                    }
                                    
                                    // Ensure it's an array
                                    if (!is_array($financialImpact)) {
                                        return 0;
                                    }
                                    
                                    return $financialImpact['profit'] ?? 0;
                                } catch (\Exception $e) {
                                    return 0;
                                }
                            }),
                        TextEntry::make('financial_details')
                            ->label('Detail Keuangan')
                            ->formatStateUsing(function ($record) {
                                try {
                                    if (!$record || !isset($record->financial_impact)) {
                                        return '-';
                                    }
                                    
                                    $financialImpact = $record->financial_impact;
                                    
                                    // Handle string JSON
                                    if (is_string($financialImpact)) {
                                        $financialImpact = json_decode($financialImpact, true);
                                    }
                                    
                                    // Ensure it's an array
                                    if (!is_array($financialImpact)) {
                                        return '-';
                                    }
                                    
                                    $details = [];
                                    
                                    if (isset($financialImpact['admin_dalam']) && is_array($financialImpact['admin_dalam'])) {
                                        $amount = $financialImpact['admin_dalam']['amount'] ?? 0;
                                        $details[] = 'Admin Dalam: Rp ' . number_format((float)$amount, 0, ',', '.');
                                    }
                                    
                                    if (isset($financialImpact['admin_luar']) && is_array($financialImpact['admin_luar'])) {
                                        $amount = $financialImpact['admin_luar']['amount'] ?? 0;
                                        $details[] = 'Admin Luar: Rp ' . number_format((float)$amount, 0, ',', '.');
                                    }
                                    
                                    if (isset($financialImpact['margin'])) {
                                        $details[] = 'Margin: Rp ' . number_format((float)$financialImpact['margin'], 0, ',', '.');
                                    }
                                    
                                    return empty($details) ? '-' : implode(', ', $details);
                                    
                                } catch (\Exception $e) {
                                    return 'Error: ' . $e->getMessage();
                                }
                            }),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'store', 'sumberDana']);
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
