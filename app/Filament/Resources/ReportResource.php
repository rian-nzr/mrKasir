<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Report;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Resources\ReportResource\Pages;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class ReportResource extends Resource implements HasShieldPermissions
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

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Laporan Keuangan';

    protected static ?string $pluralLabel = 'Laporan Keuangan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Setting Laporan')
                ->schema([
                    Forms\Components\ToggleButtons::make('report_type')
                    ->options([
                        'pemasukan' => 'Pemasukan',
                        'pengeluaran' => 'Pengeluaran'
                    ])
                    ->colors([
                        'pemasukan' => 'success',
                        'pengeluaran' => 'danger'
                    ])
                    ->icons([
                        'pemasukan' => 'heroicon-o-arrow-down-circle',
                        'pengeluaran' => 'heroicon-o-arrow-up-circle',
                    ])
                    ->default('pemasukan')
                    ->grouped(),
                    Forms\Components\DatePicker::make('start_date')
                        ->required(),
                    Forms\Components\DatePicker::make('end_date')
                        ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\IconColumn::make('report_type')
                    ->icon(fn (string $state): string => match ($state) {
                        'pemasukan' => 'heroicon-o-arrow-down-circle',
                        'pengeluaran' => 'heroicon-o-arrow-up-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pemasukan' => 'success',
                        'pengeluaran' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
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
                Tables\Actions\Action::make('download')
                ->label('Download') // Label di tombol
                ->icon('heroicon-m-arrow-down-tray') // Icon download dari Heroicons
                ->color('primary') // Warna tombol (biru)
                ->url(fn ($record) => asset('storage/' . $record->path_file))
                ->openUrlInNewTab(true), // Membuka URL di tab baru,
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
        ];
    }
}
