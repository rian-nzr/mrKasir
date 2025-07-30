<?php

namespace App\Filament\Resources\CategoryResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Harga')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('orderProducts.count')
                    ->label('Jumlah Order')
                    ->alignCenter()
                    ->getStateUsing(function (Product $record) {
                        return $record->orderProducts->count();
                    }),
                Tables\Columns\TextColumn::make('orderProducts.total_price')
                    ->label('Total Order')
                    ->alignCenter()
                    ->getStateUsing(function (Product $record) {
                        return 'Rp ' . number_format($record->orderProducts->sum('unit_price'), 0, ',', '.');
                    })
            ])
            ->filters([
            ])
            ->headerActions([
            ])
            ->actions([
            ])
            ->bulkActions([
            ]);
    }
}
