<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentMethodResource\Pages;
use App\Filament\Resources\PaymentMethodResource\RelationManagers;
use App\Models\PaymentMethod;
use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
class PaymentMethodResource extends Resource implements HasShieldPermissions
{
    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'create',
            'update',
            'delete_any',
        ];
    }
    protected static ?string $model = PaymentMethod::class;

    protected static ?string $navigationIcon = 'heroicon-m-newspaper';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Metode Pembayaran';
    protected static ?string $pluralLabel = 'Metode Pembayaran';

    protected static ?string $navigationGroup = 'Menejemen keuangan';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('store_id')
                    ->label('Toko')
                    ->options(Store::where('is_active', true)->pluck('name', 'id'))
                    ->default(function () {
                        $user = Auth::user();
                        if ($user->isSuperAdmin()) {
                            return Session::get('selected_store_id');
                        }
                        return $user->store_id;
                    })
                    ->required()
                    ->disabled(!Auth::user()->isSuperAdmin()),
                Forms\Components\TextInput::make('name')
                    ->label('Metode Pembayaran')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('image')
                    ->label('Logo Metode Pembayaran')
                    ->image()
                    ->required(),
                Forms\Components\Toggle::make('is_cash')
                    ->label('Metode Pembayaran Cash')
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        if ($state) {
                            $set('is_ewallet', false);
                        }
                    }),
                Forms\Components\Toggle::make('is_ewallet')
                    ->label('E-Wallet')
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        if ($state) {
                            $set('is_cash', false);
                        }
                    }),
                Forms\Components\Section::make('Detail E-Wallet / Bank')
                    ->schema([
                        Forms\Components\TextInput::make('balance')
                            ->label('Saldo')
                            ->numeric()
                            ->default(0)
                            ->prefix('Rp')
                            ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') : '0'),
                        Forms\Components\TextInput::make('account_number')
                            ->label('Nomor Rekening / Nomor E-Wallet')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('account_name')
                            ->label('Nama Pemilik')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('bank_name')
                            ->label('Nama Bank / Provider E-Wallet')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3),
                    ])
                    ->visible(fn (callable $get) => $get('is_ewallet') || !$get('is_cash'))
                    ->columns(2),
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('store.name')
                    ->label('Toko')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image')
                    ->label('Logo Pembayaran'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Metode Pembayaran')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Cash' => 'success',
                        'E-Wallet' => 'info',
                        'Bank Transfer' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('formatted_balance')
                    ->label('Saldo')
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderBy('balance', $direction);
                    })
                    ->color('success')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('account_info')
                    ->label('Info Akun')
                    ->wrap()
                    ->placeholder('-'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('topup')
                    ->label('Top Up')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->visible(fn (PaymentMethod $record) => $record->is_ewallet || !$record->is_cash)
                    ->form([
                        TextInput::make('amount')
                            ->label('Jumlah Top Up')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(1000)
                            ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') : ''),
                    ])
                    ->action(function (PaymentMethod $record, array $data) {
                        $amount = str_replace(['.', ','], '', $data['amount']);
                        $record->recordTransaction('topup', (float) $amount);
                        
                        Notification::make()
                            ->success()
                            ->title('Top Up Berhasil')
                            ->body('Saldo ' . $record->name . ' berhasil ditambah Rp ' . number_format($amount, 0, ',', '.'))
                            ->send();
                    }),
                Action::make('withdraw')
                    ->label('Tarik Saldo')
                    ->icon('heroicon-o-minus-circle')
                    ->color('danger')
                    ->visible(fn (PaymentMethod $record) => ($record->is_ewallet || !$record->is_cash) && $record->balance > 0)
                    ->form([
                        TextInput::make('amount')
                            ->label('Jumlah Penarikan')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(1000)
                            ->maxValue(fn (PaymentMethod $record) => $record->balance)
                            ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') : ''),
                    ])
                    ->action(function (PaymentMethod $record, array $data) {
                        $amount = str_replace(['.', ','], '', $data['amount']);
                        $record->recordTransaction('withdraw', (float) $amount);
                        
                        Notification::make()
                            ->success()
                            ->title('Penarikan Berhasil')
                            ->body('Saldo ' . $record->name . ' berhasil dikurangi Rp ' . number_format($amount, 0, ',', '.'))
                            ->send();
                    }),
                Action::make('transfer')
                    ->label('Transfer Saldo')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('warning')
                    ->visible(fn (PaymentMethod $record) => ($record->is_ewallet || !$record->is_cash) && $record->balance > 0)
                    ->form([
                        Forms\Components\Select::make('target_payment_method_id')
                            ->label('Transfer Ke')
                            ->required()
                            ->options(function (PaymentMethod $record) {
                                return PaymentMethod::where('store_id', $record->store_id)
                                    ->where('id', '!=', $record->id)
                                    ->where('is_active', true)
                                    ->where(function($query) {
                                        $query->where('is_ewallet', true)
                                            ->orWhere('is_cash', false);
                                    })
                                    ->pluck('name', 'id');
                            })
                            ->searchable(),
                        TextInput::make('amount')
                            ->label('Jumlah Transfer')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(1000)
                            ->maxValue(fn (PaymentMethod $record) => $record->balance)
                            ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') : ''),
                        Forms\Components\Textarea::make('description')
                            ->label('Keterangan')
                            ->placeholder('Opsional - keterangan transfer'),
                    ])
                    ->action(function (PaymentMethod $record, array $data) {
                        try {
                            $amount = str_replace(['.', ','], '', $data['amount']);
                            $targetPaymentMethod = PaymentMethod::find($data['target_payment_method_id']);
                            $referenceNumber = $record->transferTo($targetPaymentMethod, (float) $amount, $data['description']);
                            
                            Notification::make()
                                ->success()
                                ->title('Transfer Berhasil')
                                ->body('Transfer Rp ' . number_format($amount, 0, ',', '.') . ' dari ' . $record->name . ' ke ' . $targetPaymentMethod->name . ' berhasil. Ref: ' . $referenceNumber)
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Transfer Gagal')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentMethods::route('/'),
            'create' => Pages\CreatePaymentMethod::route('/create'),
            'edit' => Pages\EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}
