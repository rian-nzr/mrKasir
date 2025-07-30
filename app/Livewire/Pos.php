<?php

namespace App\Livewire;

use Filament\Forms;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Livewire\Component;
use App\Models\Category;


use Filament\Forms\Form;
use App\Models\OrderProduct;

use Livewire\WithPagination;
use App\Models\PaymentMethod;
use Filament\Forms\Contracts\HasForms;

use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Services\DirectPrintService;

class Pos extends Component implements HasForms
{

    use InteractsWithForms;
    use WithPagination;

    public $categories;
    public $selectedCategory;
    public $search = '';
    public $print_via_mobile = false;
    public $barcode = '';
    public $name = 'Umum';
    public $payment_method_id = 0;
    public $payment_methods;
    public $order_items = [];
    public $total_price;
    public $showConfirmationModal = false;
    public $orderToPrint = null;

    protected $listeners = [
        'scanResult' => 'handleScanResult',
    ];

    public function mount()
    {
        $settings = Setting::first();
        $this->print_via_mobile = $settings->print_via_mobile ?? $this->print_via_mobile = false;

        // Mengambil data kategori dan menambahkan data 'Semua' sebagai pilihan pertama
        $this->categories = collect([['id' => null, 'name' => 'Semua']])->merge(Category::all());

        // Jika session 'orderItems' ada, maka ambil data nya dan simpan ke dalam property $order_items
        // Session 'orderItems' digunakan untuk menyimpan data order sementara sebelum di checkout
        if (session()->has('orderItems')) {
            $this->order_items = session('orderItems');
        }

        $this->payment_methods = PaymentMethod::all();
    }

    public function render()
    {
        return view('livewire.pos', [
            'products' => Product::where('stock', '>', 0)->where('is_active', 1)
                ->when($this->selectedCategory !== null, function ($query) {
                    return $query->where('category_id', $this->selectedCategory);
                })
                ->search($this->search)
                ->paginate(10)
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2) // Membagi form menjadi 5 kolom
                    ->schema([
                        // Input Name Customer
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->default(fn () => $this->name)
                            ->label('Name Customer')
                            ->nullable()
                            ->maxLength(255)
                            ->columnSpan(1), // Menggunakan 1 kolom

                       
                        // Input Payment Method
                        Forms\Components\Select::make('payment_method_id')
                            ->required()
                            ->label('Metode Pembayaran')
                            ->options($this->payment_methods->pluck('name', 'id'))
                            ->columnSpan(1), // Menggunakan 1 kolom
                    ])
            ]);
    }

    public function updatedBarcode($barcode)
    {

        $product = Product::where('barcode', $barcode)
                    ->where('is_active' , true)->first();

        if ($product) {
            $this->addToOrder($product->id);
        } else {
            Notification::make()
                ->title('Product not found '.$barcode)
                ->danger()
                ->send();
        }
            
            // Reset barcode
            $this->barcode = '';
    }

    public function handleScanResult($decodedText)
    {
        $product = Product::where('barcode', $decodedText)
        ->where('is_active' , true)->first();

        if ($product) {
            $this->addToOrder($product->id);
        } else {
            Notification::make()
                ->title('Product not found '.$decodedText)
                ->danger()
                ->send();
        }

        // Reset barcode
        $this->barcode = '';
    }

    public function setCategory($categoryId = null)
    {
        $this->selectedCategory = $categoryId;
        // $this->loadMenus();
    }

    public function addToOrder($productId)
    {
        $product = Product::find($productId);

        if ($product) {

            // Cari apakah item sudah ada di dalam order
            $existingItemKey = array_search($productId, array_column($this->order_items, 'product_id'));

            // Jika item sudah ada, tambahkan 1 quantity
            if ($existingItemKey !== false) {
                if ($this->order_items[$existingItemKey]['quantity'] >= $product->stock) {
                    Notification::make()
                        ->title('Stok barang tidak mencukupi')
                        ->danger()
                        ->send();
                    return;
                } else {
                    $this->order_items[$existingItemKey]['quantity']++;
                }
            } 

            // Jika item belum ada, tambahkan item baru ke dalam order
            else {
                $this->order_items[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image_url' => $product->image_url,
                    'quantity' => 1,
                ];
            }

            // Simpan perubahan order ke session
            session()->put('orderItems', $this->order_items);

        }
    }

    public function loadOrderItems($orderItems)
    {
        $this->order_items = $orderItems;
        session()->put('orderItems', $orderItems);
    }

    public function increaseQuantity($product_id)
    {
        $product = Product::find($product_id);

        if (!$product) {
            Notification::make()
                ->title('Produk tidak ditemukan')
                ->danger()
                ->send();
            return;
        }

        // Loop setiap item yang ada di cart
        foreach($this->order_items as $key => $item) {
            // Jika item yang sedang di-loop sama dengan item yang ingin di tambah
            if ($item['product_id'] == $product_id) {
                // Jika quantity item ditambah 1 masih kurang dari atau sama dengan stok produk maka tambah 1 quantity
                if ($item['quantity'] + 1 <= $product->stock) {
                    $this->order_items[$key]['quantity']++;
                } 
                // Jika quantity item yang ingin di tambah lebih besar dari stok produk maka tampilkan notifikasi
                else {
                    Notification::make()
                    ->title('Stok barang tidak mencukupi')
                    ->danger()
                    ->send();
                }
                // Berhenti loop karena item yang ingin di tambah sudah di temukan
                break;
            }
        }

        session()->put('orderItems', $this->order_items);
    }

    public function decreaseQuantity($product_id)
    {
        // Loop setiap item yang ada di cart
        foreach($this->order_items as $key => $item) {
            // Jika item yang sedang di-loop sama dengan item yang ingin di kurangi
            if ($item['product_id'] == $product_id) {
                // Jika quantity item lebih dari 1 maka kurangi 1 quantity
                if ($this->order_items[$key]['quantity'] > 1) {
                    $this->order_items[$key]['quantity']--;
                } 
                // Jika quantity item 1 maka hapus item dari cart
                else {
                    unset($this->order_items[$key]);
                    $this->order_items = array_values($this->order_items);
                }
                break;
            }
        }
        // Simpan perubahan cart ke session
        session()->put('orderItems', $this->order_items);
    }

    public function calculateTotal()
    {
        // Inisialisasi total harga
        $total = 0;

        // Loop setiap item yang ada di cart
        foreach($this->order_items as $item) {
            // Tambahkan harga setiap item ke total
            $total += $item['quantity'] * $item['price'];
        }

        // Simpan total harga di property $total_price
        $this->total_price = $total;

        // Return total harga
        return $total;
    }


    public function resetOrder()
    {
        // Hapus semua session terkait
        session()->forget(['orderItems', 'name', 'payment_method_id']);

        // Reset variabel Livewire
        $this->order_items = [];
        $this->payment_method_id = null;
        $this->total_price = 0;
    }



    public function checkout()
    {
        $this->validate([
            'name' => 'string|max:255',
            'payment_method_id' => 'required'
        ]);

        $payment_method_id_temp = $this->payment_method_id;

        if (session('orderItems') === null || count(session('orderItems')) == 0) {
            Notification::make()
            ->title('Keranjang kosong')
            ->danger()
            ->send();
        } else {
            $order = Order::create([
            'name' => $this->name,
            'total_price' => $this->calculateTotal(),
            'payment_method_id' => $payment_method_id_temp
            ]);
            foreach($this->order_items as $item) {
                OrderProduct::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price']
                ]);
            }
             // Simpan ID order untuk cetak
            $this->orderToPrint = $order->id;

            // Tampilkan modal konfirmasi
            $this->showConfirmationModal = true;

            Notification::make()
            ->title('Order berhasil disimpan')
            ->success()
            ->send();

            $this->name = 'umum';
            $this->payment_method_id = null;
            $this->total_price = 0;
            $this->order_items = [];
            session()->forget(['orderItems']);
        }
    }


    public function printLocalKabel()
    {
        $directPrint = app(DirectPrintService::class);

        $directPrint->print($this->orderToPrint);

        $this->showConfirmationModal = false;
        $this->orderToPrint = null;
    }

    public function printBluetooth()
    {
        $order = Order::with('paymentMethod')->findOrFail($this->orderToPrint);
        $items = OrderProduct::with('product')->where('order_id', $order->id)->get();

        $this->dispatch('doPrintReceipt', 
            store: Setting::first(),
            order: $order,
            items: $items,
            date: $order->created_at->format('d-m-Y H:i:s')
        );

        $this->showConfirmationModal = false;
        $this->orderToPrint = null;
    }
    public function printOrder($order_id)
    {
        $order = Order::with('paymentMethod')->findOrFail($order_id);
        $items = OrderProduct::with('product')->where('order_id', $order_id)->get();

        $this->dispatch('doPrintReceipt', 
            store: Setting::first(),
            order: $order,
            items: $items,
            date: $order->created_at->format('d-m-Y H:i:s')
        );

    }

}
