<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Order;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class OrderProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'Order Items';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\ImageColumn::make('product.image')
                    ->label('Gambar'),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Nama Produk'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Jumlah'),
                Tables\Columns\TextColumn::make('product.price')
                    ->label('Harga Satuan')
                    ->prefix('Rp ')
                    ->numeric(),
                // Tambahkan kolom total harga
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Total Harga')
                    ->prefix('Rp ')
                    ->numeric()
                    ->getStateUsing(fn($record) => ($record->quantity ?? 0) * ($record->unit_price ?? 0)) // Hitung langsung dari data record
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function calculateTotal($records)
    {
        $total = 0;
        foreach ($records as $item) {
            $total += $item['quantity'] * $item['price'];
        }
        return $total;
    }
}
