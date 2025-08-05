<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashierShiftResource\Pages;
use App\Models\CashierShift;
use App\Services\CashierShiftService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Colors\Color;

class CashierShiftResource extends Resource
{
    protected static ?string $model = CashierShift::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Shift Kasir';

    protected static ?string $modelLabel = 'Shift Kasir';

    protected static ?string $pluralModelLabel = 'Shift Kasir';

    protected static ?string $navigationGroup = 'Kasir';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Shift')
                    ->schema([
                        Forms\Components\TextInput::make('shift_number')
                            ->label('Nomor Shift')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('user_id')
                            ->label('Kasir')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'open' => 'Terbuka',
                                'closed' => 'Ditutup',
                                'suspended' => 'Ditangguhkan',
                            ])
                            ->required()
                            ->default('open'),
                        Forms\Components\TextInput::make('location')
                            ->label('Lokasi/Counter')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Kas Awal & Akhir')
                    ->schema([
                        Forms\Components\TextInput::make('opening_cash')
                            ->label('Kas Awal')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->step(1000),
                        Forms\Components\TextInput::make('closing_cash')
                            ->label('Kas Akhir')
                            ->numeric()
                            ->prefix('Rp')
                            ->step(1000)
                            ->visible(fn (string $operation): bool => $operation === 'edit'),
                        Forms\Components\TextInput::make('expected_cash')
                            ->label('Kas yang Diharapkan')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (string $operation): bool => $operation === 'edit'),
                        Forms\Components\TextInput::make('cash_difference')
                            ->label('Selisih Kas')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (string $operation): bool => $operation === 'edit'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Catatan')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan Pembukaan')
                            ->rows(3),
                        Forms\Components\Textarea::make('closing_notes')
                            ->label('Catatan Penutupan')
                            ->rows(3)
                            ->visible(fn (string $operation): bool => $operation === 'edit'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shift_number')
                    ->label('Nomor Shift')
                    ->sortable()
                    ->searchable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Kasir')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'open',
                        'gray' => 'closed',
                        'warning' => 'suspended',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Terbuka',
                        'closed' => 'Ditutup',
                        'suspended' => 'Ditangguhkan',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('location')
                    ->label('Lokasi')
                    ->toggleable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('opening_cash')
                    ->label('Kas Awal')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('closing_cash')
                    ->label('Kas Akhir')
                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : '-')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('cash_difference')
                    ->label('Selisih')
                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : '-')
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_sales')
                    ->label('Total Penjualan')
                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : '-')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_transactions')
                    ->label('Transaksi')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('opened_at')
                    ->label('Dibuka')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('closed_at')
                    ->label('Ditutup')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Durasi')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Terbuka',
                        'closed' => 'Ditutup',
                        'suspended' => 'Ditangguhkan',
                    ]),

                Tables\Filters\Filter::make('opened_at')
                    ->form([
                        Forms\Components\DatePicker::make('opened_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('opened_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['opened_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('opened_at', '>=', $date),
                            )
                            ->when(
                                $data['opened_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('opened_at', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('user')
                    ->label('Kasir')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (CashierShift $record) => $record->isOpen()),
                Tables\Actions\Action::make('close_shift')
                    ->label('Tutup Shift')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->visible(fn (CashierShift $record) => $record->isOpen())
                    ->form([
                        Forms\Components\TextInput::make('closing_cash')
                            ->label('Jumlah Kas Akhir')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->step(1000),
                        Forms\Components\Textarea::make('closing_notes')
                            ->label('Catatan Penutupan')
                            ->rows(3),
                    ])
                    ->action(function (CashierShift $record, array $data) {
                        $service = app(CashierShiftService::class);
                        $service->closeShift(
                            $record->id,
                            $data['closing_cash'],
                            $data['closing_notes'] ?? null
                        );
                    })
                    ->successNotificationTitle('Shift berhasil ditutup')
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('suspend_shift')
                    ->label('Tangguhkan')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->visible(fn (CashierShift $record) => $record->isOpen())
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Alasan Penangguhan')
                            ->rows(3)
                            ->required(),
                    ])
                    ->action(function (CashierShift $record, array $data) {
                        $service = app(CashierShiftService::class);
                        $service->suspendShift($record->id, $data['notes']);
                    })
                    ->successNotificationTitle('Shift berhasil ditangguhkan'),

                Tables\Actions\Action::make('resume_shift')
                    ->label('Lanjutkan')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (CashierShift $record) => $record->status === 'suspended')
                    ->action(function (CashierShift $record) {
                        $service = app(CashierShiftService::class);
                        $service->resumeShift($record->id);
                    })
                    ->successNotificationTitle('Shift berhasil dilanjutkan')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('delete_cashier_shift')),
                ]),
            ])
            ->defaultSort('opened_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Shift')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('shift_number')
                                    ->label('Nomor Shift')
                                    ->weight(FontWeight::Bold),
                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('Kasir'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'open' => 'success',
                                        'closed' => 'gray',
                                        'suspended' => 'warning',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'open' => 'Terbuka',
                                        'closed' => 'Ditutup',
                                        'suspended' => 'Ditangguhkan',
                                        default => $state,
                                    }),
                                Infolists\Components\TextEntry::make('location')
                                    ->label('Lokasi'),
                                Infolists\Components\TextEntry::make('opened_at')
                                    ->label('Dibuka')
                                    ->dateTime('d/m/Y H:i:s'),
                                Infolists\Components\TextEntry::make('closed_at')
                                    ->label('Ditutup')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('duration')
                                    ->label('Durasi')
                                    ->placeholder('-'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Ringkasan Kas')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('opening_cash')
                                    ->label('Kas Awal')
                                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.')),
                                Infolists\Components\TextEntry::make('closing_cash')
                                    ->label('Kas Akhir')
                                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : '-'),
                                Infolists\Components\TextEntry::make('expected_cash')
                                    ->label('Kas yang Diharapkan')
                                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : '-'),
                                Infolists\Components\TextEntry::make('cash_difference')
                                    ->label('Selisih Kas')
                                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : '-')
                                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray')),
                            ]),
                    ]),

                Infolists\Components\Section::make('Ringkasan Penjualan')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_sales')
                                    ->label('Total Penjualan')
                                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : 'Rp 0'),
                                Infolists\Components\TextEntry::make('total_transactions')
                                    ->label('Total Transaksi')
                                    ->formatStateUsing(fn ($state) => $state . ' transaksi'),
                                Infolists\Components\TextEntry::make('total_discounts')
                                    ->label('Total Diskon')
                                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : 'Rp 0'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Catatan')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Catatan Pembukaan')
                            ->placeholder('Tidak ada catatan'),
                        Infolists\Components\TextEntry::make('closing_notes')
                            ->label('Catatan Penutupan')
                            ->placeholder('Tidak ada catatan'),
                    ])
                    ->visible(fn ($record) => $record->notes || $record->closing_notes),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashierShifts::route('/'),
            'create' => Pages\CreateCashierShift::route('/create'),
            'view' => Pages\ViewCashierShift::route('/{record}'),
            'edit' => Pages\EditCashierShift::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $openShifts = static::getModel()::where('status', 'open')->count();
            return $openShifts > 0 ? (string) $openShifts : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
