<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\PaymentMethod;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Mike42\Escpos\Printer;
use App\Models\OrderProduct;
use Mike42\Escpos\EscposImage;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use App\Services\DirectPrintService;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\Repeater;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\OrderResource\Pages;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class OrderResource extends Resource implements HasShieldPermissions
{
    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete_any',
        ];
    }
    protected static ?string $model = Order::class;



    protected static ?string $navigationIcon = 'heroicon-m-shopping-bag';

    protected static ?string $navigationLabel = 'Pemasukan';

    protected static ?string $pluralLabel = 'Pemasukan';

    protected static ?string $navigationGroup = 'Menejemen keuangan';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()->orderBy('created_at', 'desc');
}


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->maxLength(255)
                            ->nullable()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255)
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('birthday'),
                    ]),
                Forms\Components\Section::make('Produk dipesan')->schema([
                    self::getItemsRepeater(),
                ]),


                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('total_price')
                            ->required()
                            ->readOnly()
                            ->columnSpan(1)
                            ->numeric(),

                        Forms\Components\Select::make('payment_method_id')
                            ->label('Metode Pembayaran')
                            ->options(function () {
                                $user = auth()->user();
                                $storeId = null;
                                
                                if ($user->isSuperAdmin()) {
                                    $storeId = session('selected_store_id');
                                } else {
                                    $storeId = $user->store_id;
                                }
                                
                                if ($storeId) {
                                    return PaymentMethod::where('store_id', $storeId)->pluck('name', 'id');
                                }
                                
                                return PaymentMethod::pluck('name', 'id');
                            })
                            ->reactive()
                            ->columnSpan(1)
                            ->required(),
                        Forms\Components\Hidden::make('is_cash')
                            ->dehydrated(),
                        Forms\Components\Hidden::make('cashier_shift_id')
                            ->dehydrated(),
                            Forms\Components\Textarea::make('note')
                            ->columnSpanFull()
                            ->columnSpan(2),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pemesan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total Harga')
                    ->numeric(),
                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->label('Metode Pembayaran')
                    ->numeric(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('Start Date'),
                        DatePicker::make('end_date')
                            ->label('End Date'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['start_date'], fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['end_date'], fn ($query, $date) => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Action::make('Print')
                    ->label('Cetak')
                    ->hidden(fn () => Setting::first()->value('print_via_mobile')) // Ambil nilai dari model lain
                    ->action(function (Order $record) {
                        $directPrint = app(DirectPrintService::class);
                        $directPrint->print($record->id);
                    })
                    ->icon('heroicon-o-printer')
                    ->color('amber'),
                Action::make('Print')
                    ->label('Cetak')
                    ->hidden(fn () => Setting::first()->value('print_via_mobile') == false) // Ambil nilai dari model lain
                    ->action(fn ($record,$livewire) => $livewire->printOrder($record->id))
                    ->icon('heroicon-o-printer')
                    ->color('amber'),

                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make()
                ->color('warning')
                ->label('Detail'),
               
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
            ]);
    }
    public static function infolist(Infolist $infolist): Infolist
{
    return $infolist
        ->schema([
            TextEntry::make('name')
            ->label('Nama Customer :')
            ->badge()
            ->color('primary')
            ->weight(FontWeight::Bold),
            TextEntry::make('paymentMethod.name')
            ->label('Metode Pembayaran :')
            ->badge()
            ->color('primary')
            ->weight(FontWeight::Bold),
            TextEntry::make('created_at')
            ->label('Tanggal Transaksi:')
            ->badge()
            ->color('primary')
            ->weight(FontWeight::Bold),
        ])->columns(3);
}

    public static function getRelations(): array
    {
        return [
            OrderResource\RelationManagers\OrderProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }

    public static function getItemsRepeater(): Repeater
    {
        return Repeater::make('orderProducts')
            ->relationship()
            ->live()
            ->columns([
                'md' => 10,
            ])
            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                self::updateTotalPrice($get, $set);
            })
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Produk')
                    ->required()
                    ->options(Product::query()->where('stock', '>', 1)->pluck('name', 'id'))
                    ->columnSpan([
                        'md' => 5
                    ])
                    ->afterStateHydrated(function (Forms\Set $set, Forms\Get $get, $state) {
                        $product = Product::find($state);
                        $set('unit_price', $product->price ?? 0);
                        $set('stock', $product->stock ?? 0);
                    })
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        $product = Product::find($state);
                        $set('unit_price', $product->price ?? 0);
                        $set('stock', $product->stock ?? 0);
                        $quantity = $get('quantity') ?? 1;
                        $stock = $get('stock');
                        self::updateTotalPrice($get, $set);
                    })
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->columnSpan([
                        'md' => 1
                    ])
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        $stock = $get('stock');
                        if ($state > $stock) {
                            $set('quantity', $stock);
                            Notification::make()
                                ->title('Stok tidak mencukupi')
                                ->warning()
                                ->send();
                        }

                        self::updateTotalPrice($get, $set);
                    }),
                Forms\Components\TextInput::make('stock')
                    ->required()
                    ->numeric()
                    ->readOnly()
                    ->columnSpan([
                        'md' => 1
                    ]),
                Forms\Components\TextInput::make('unit_price')
                    ->label('Harga saat ini')
                    ->required()
                    ->numeric()
                    ->readOnly()
                    ->columnSpan([
                        'md' => 3
                    ]),

            ]);
    }

    protected static function updateTotalPrice(Forms\Get $get, Forms\Set $set): void
    {
        $selectedProducts = collect($get('orderProducts'))->filter(fn($item) => !empty($item['product_id']) && !empty($item['quantity']));

        $prices = Product::find($selectedProducts->pluck('product_id'))->pluck('price', 'id');
        $total = $selectedProducts->reduce(function ($total, $product) use ($prices) {
            return $total + ($prices[$product['product_id']] * $product['quantity']);
        }, 0);

        $set('total_price', $total);
    }

    protected static function updateExcangePaid(Forms\Get $get, Forms\Set $set): void
    {
        $paidAmount = (int) $get('paid_amount') ?? 0;
        $totalPrice = (int) $get('total_price') ?? 0;
        $exchangePaid = $paidAmount - $totalPrice;
        $set('change_amount', $exchangePaid);
    }
}
