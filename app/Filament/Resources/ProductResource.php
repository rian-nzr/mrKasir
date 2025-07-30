<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Milon\Barcode\DNS1D;
use Filament\Tables\Table;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Resources\Resource;
use App\Filament\Clusters\Products;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProductResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProductResource\RelationManagers;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class ProductResource extends Resource implements HasShieldPermissions
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
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-m-square-3-stack-3d';

    protected static ?string $navigationLabel = 'Produk';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = 'Menejemen Produk';


    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Produk')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('category_id')
                    ->label('Kategori Produk')
                    ->relationship('category', 'name'),
                Forms\Components\Select::make('group_id')
                    ->label('Grup Produk')
                    ->relationship('group', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('stock')
                    ->label('Stok Produk')
                    ->required()
                    ->numeric()
                    ->default(1),
                Forms\Components\TextInput::make('cost_price')
                    ->label('Harga Beli/Modal')
                    ->prefix('Rp')
                    ->placeholder('0')
                    ->helperText('Harga pembelian atau modal produk (contoh: 50000 atau 50.000)')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') : '')
                    ->dehydrateStateUsing(fn ($state) => $state ? (int) str_replace(['.', ',', ' '], '', $state) : null)
                    ->extraInputAttributes([
                        'x-data' => '{ 
                            formatCurrency() { 
                                let value = $el.value.replace(/[^\d]/g, "");
                                if (value) {
                                    $el.value = parseInt(value).toLocaleString("id-ID");
                                }
                            }
                        }',
                        'x-on:input' => 'formatCurrency()',
                        'x-on:blur' => 'formatCurrency()',
                    ]),
                Forms\Components\TextInput::make('price')
                    ->label('Harga Jual')
                    ->required()
                    ->prefix('Rp')
                    ->placeholder('0')
                    ->helperText('Harga jual produk (contoh: 75000 atau 75.000)')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') : '')
                    ->dehydrateStateUsing(fn ($state) => $state ? (int) str_replace(['.', ',', ' '], '', $state) : null)
                    ->extraInputAttributes([
                        'x-data' => '{ 
                            formatCurrency() { 
                                let value = $el.value.replace(/[^\d]/g, "");
                                if (value) {
                                    $el.value = parseInt(value).toLocaleString("id-ID");
                                }
                            }
                        }',
                        'x-on:input' => 'formatCurrency()',
                        'x-on:blur' => 'formatCurrency()',
                    ]),
                Forms\Components\Toggle::make('is_active')
                    ->label('Produk Aktif')
                    ->required(),
                Forms\Components\FileUpload::make('image')
                    ->label('Gambar Produk')
                    ->image()
                    ->maxSize(1024),
                Forms\Components\TextInput::make('barcode')
                    ->label('Barcode Produk')
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi Produk')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image')
                    ->label('Gambar')
                    ->circular(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('group.name')
                    ->label('Grup Produk')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stok')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost_price')
                    ->label('Harga Beli')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('profit')
                    ->label('Laba')
                    ->getStateUsing(fn ($record) => $record->profit)
                    ->money('IDR')
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray'))
                    ->sortable(false)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('profit_percentage')
                    ->label('% Laba')
                    ->getStateUsing(fn ($record) => $record->profit_percentage . '%')
                    ->color(fn ($record) => $record->profit_percentage > 0 ? 'success' : ($record->profit_percentage < 0 ? 'danger' : 'gray'))
                    ->sortable(false)
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('barcode')
                    ->label('Barcode')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('group_id')
                    ->label('Grup Produk')
                    ->relationship('group', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('printBarcodes')
                    ->label('Cetak Barcode')
                    ->icon('heroicon-o-printer')
                    ->action(fn ($records) => self::generateBulkBarcode($records))
                    ->color('success'),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('printBarcodes')
                    ->label('Cetak Barcode')
                    ->icon('heroicon-o-printer')
                    ->action(fn () => self::generateBulkBarcode(Product::all()))
                    ->color('success'),
            ]);;
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
            'index' => Pages\ListProducts::route('/'),
        ];
    }

    protected static function generateBulkBarcode($records)
    {
        $barcodes = [];
        $barcodeGenerator = new DNS1D();

        // Validasi records tidak kosong
        if (empty($records) || !is_iterable($records)) {
            throw new \Exception('Tidak ada produk yang dipilih atau data tidak valid');
        }

        foreach ($records as $product) {
            // Validasi product tidak null dan memiliki atribut yang diperlukan
            if (!$product || !$product->name || !$product->barcode) {
                continue; // Skip produk yang tidak valid
            }

            try {
                // Debug: Log data produk
                \Log::info('Processing product:', [
                    'name' => $product->name,
                    'price' => $product->price,
                    'barcode' => $product->barcode
                ]);

                $barcodes[] = [
                    'name' => $product->name ?? 'Nama tidak tersedia',
                    'price' => $product->price ?? 0,
                    'barcode' => 'data:image/png;base64,' . $barcodeGenerator->getBarcodePNG($product->barcode, 'C128'),
                    'number' => $product->barcode
                ];
            } catch (\Exception $e) {
                // Log error untuk debugging
                \Log::error('Error generating barcode for product:', [
                    'product_id' => $product->id ?? 'unknown',
                    'barcode' => $product->barcode ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        // Validasi ada barcode yang berhasil digenerate
        if (empty($barcodes)) {
            throw new \Exception('Tidak ada barcode yang dapat digenerate. Pastikan produk memiliki barcode yang valid.');
        }

        // Generate PDF
        $pdf = Pdf::loadView('pdf.barcodes', compact('barcodes'))->setPaper('a4', 'portrait');

        // Simpan PDF ke storage (opsional)
        $pdfPath = storage_path('app/public/barcodes.pdf');
        $pdf->save($pdfPath);

        // Kembalikan response download tanpa metode header()
        return response()->streamDownload(fn () => print($pdf->output()), 'barcodes.pdf');
    }

}
