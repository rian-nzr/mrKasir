<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Expense;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ExpenseResource\Pages;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class ExpenseResource extends Resource implements HasShieldPermissions
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
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-m-currency-dollar';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Pengeluaran';

    protected static ?string $pluralLabel = 'Pengeluaran';

    protected static ?string $navigationGroup = 'Menejemen keuangan';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()->orderBy('date_expense', 'desc');
}

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Pengeluaran')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('note')
                    ->label('Catatan')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('date_expense')
                    ->label('Tanggal Pengeluaran')
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->label('Jumlah Pengeluaran')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date_expense')
                    ->label('Tanggal Pengeluaran')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pengeluaran')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah Pengeluaran')
                    ->numeric(),
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
                Filter::make('Hari Ini')
                ->query(fn ($query) => $query->whereDate('date_expense', Carbon::today()))
                ->label('Hari Ini'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
            ]);
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
