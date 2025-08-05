<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashOutResource\Pages;
use App\Models\CashOut;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class CashOutResource extends Resource
{
    protected static ?string $model = CashOut::class;

    protected static ?string $navigationIcon = 'heroicon-o-minus-circle';

    protected static ?string $navigationLabel = 'Pengeluaran Kas';

    protected static ?string $modelLabel = 'Pengeluaran Kas';

    protected static ?string $pluralModelLabel = 'Pengeluaran Kas';

    protected static ?string $navigationGroup = 'Kasir';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pengeluaran')
                    ->schema([
                        Forms\Components\Select::make('cashier_shift_id')
                            ->label('Shift Kasir')
                            ->relationship('cashierShift', 'shift_number')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn ($context) => $context === 'edit'),

                        Forms\Components\Select::make('user_id')
                            ->label('Kasir')
                            ->relationship('user', 'name')
                            ->required()
                            ->disabled(fn ($context) => $context === 'edit')
                            ->default(auth()->id()),

                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->step(1000)
                            ->minValue(1)
                            ->disabled(fn ($context) => $context === 'edit'),

                        Forms\Components\TextInput::make('reason')
                            ->label('Alasan')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn ($context) => $context === 'edit'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Detail')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull()
                            ->disabled(fn ($context) => $context === 'edit'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Menunggu Persetujuan',
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
                            ])
                            ->required()
                            ->default('pending')
                            ->disabled(fn ($context) => $context === 'create'),

                        Forms\Components\Textarea::make('approval_notes')
                            ->label('Catatan Persetujuan')
                            ->rows(3)
                            ->visible(fn ($context) => $context === 'edit')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Informasi Waktu')
                    ->schema([
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Waktu Pengajuan')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('Waktu Persetujuan')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->visible(fn ($context) => $context === 'edit')
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cashierShift.shift_number')
                    ->label('Nomor Shift')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Kasir')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Alasan')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Pengajuan')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Waktu Persetujuan')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Belum disetujui'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu Persetujuan',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
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
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (CashOut $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('approval_notes')
                            ->label('Catatan Persetujuan')
                            ->rows(3),
                    ])
                    ->action(function (CashOut $record, array $data) {
                        try {
                            $record->approve(auth()->id(), $data['approval_notes'] ?? null);
                            
                            Notification::make()
                                ->title('Pengeluaran Disetujui')
                                ->body('Pengeluaran kas berhasil disetujui')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal Menyetujui')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (CashOut $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('approval_notes')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (CashOut $record, array $data) {
                        try {
                            $record->reject(auth()->id(), $data['approval_notes']);
                            
                            Notification::make()
                                ->title('Pengeluaran Ditolak')
                                ->body('Pengeluaran kas telah ditolak')
                                ->warning()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal Menolak')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation(),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (CashOut $record) => $record->status === 'pending'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('delete_cash_out')),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListCashOuts::route('/'),
            'create' => Pages\CreateCashOut::route('/create'),
            'view' => Pages\ViewCashOut::route('/{record}'),
            'edit' => Pages\EditCashOut::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['cashierShift', 'user'])
            ->when(
                auth()->user()->hasRole('kasir'),
                fn (Builder $query) => $query->where('user_id', auth()->id())
            );
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_cash_out');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('edit_cash_out') && $record->status === 'pending';
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_cash_out') && $record->status === 'pending';
    }

    public static function canView($record): bool
    {
        return auth()->user()->can('view_cash_out');
    }
}
