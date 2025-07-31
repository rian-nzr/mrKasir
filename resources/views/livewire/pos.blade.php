<div class="grid grid-cols-1 md:grid-cols-3 gap-4" style="font-family : poppins;">
    <div class="md:col-span-2">

        <!-- Transaction Buttons -->
        <div class="mb-6 p-4 bg-gradient-to-r from-green-50 to-blue-50 dark:from-green-900/20 dark:to-blue-900/20 rounded-lg border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">Pencatatan Transaksi</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <button wire:click="openTransactionModal('transfer')" 
                    class="flex flex-col items-center p-3 bg-blue-100 dark:bg-blue-800/30 hover:bg-blue-200 dark:hover:bg-blue-800/50 rounded-lg transition-colors duration-200">
                    <i class="fas fa-exchange-alt text-blue-600 dark:text-blue-400 text-xl mb-1"></i>
                    <span class="text-sm font-medium text-blue-800 dark:text-blue-300">Transfer</span>
                </button>
                
                <button wire:click="openTransactionModal('tarik_tunai')" 
                    class="flex flex-col items-center p-3 bg-red-100 dark:bg-red-800/30 hover:bg-red-200 dark:hover:bg-red-800/50 rounded-lg transition-colors duration-200">
                    <i class="fas fa-money-bill-wave text-red-600 dark:text-red-400 text-xl mb-1"></i>
                    <span class="text-sm font-medium text-red-800 dark:text-red-300">Tarik Tunai</span>
                </button>
                
                <button wire:click="openTransactionModal('jasa_transfer')" 
                    class="flex flex-col items-center p-3 bg-green-100 dark:bg-green-800/30 hover:bg-green-200 dark:hover:bg-green-800/50 rounded-lg transition-colors duration-200">
                    <i class="fas fa-hand-holding-usd text-green-600 dark:text-green-400 text-xl mb-1"></i>
                    <span class="text-sm font-medium text-green-800 dark:text-green-300">Jasa Transfer</span>
                </button>
                
                <button wire:click="openTransactionModal('mode_pulsa')" 
                    class="flex flex-col items-center p-3 bg-purple-100 dark:bg-purple-800/30 hover:bg-purple-200 dark:hover:bg-purple-800/50 rounded-lg transition-colors duration-200">
                    <i class="fas fa-mobile-alt text-purple-600 dark:text-purple-400 text-xl mb-1"></i>
                    <span class="text-sm font-medium text-purple-800 dark:text-purple-300">Mode Pulsa</span>
                </button>
            </div>
        </div>

        <!-- Total Saldo Payment Methods -->
        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200 mb-2">Total Saldo Semua Metode Pembayaran</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                @php
                    $balanceByType = $this->getBalanceByType();
                @endphp
                <div class="text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Tunai</p>
                    <p class="text-lg font-bold text-green-600">Rp {{ number_format($balanceByType['cash'], 0, ',', '.') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">E-Wallet</p>
                    <p class="text-lg font-bold text-blue-600">Rp {{ number_format($balanceByType['ewallet'], 0, ',', '.') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Transfer</p>
                    <p class="text-lg font-bold text-purple-600">Rp {{ number_format($balanceByType['transfer'], 0, ',', '.') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Keseluruhan</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $this->getFormattedTotalBalance() }}</p>
                </div>
            </div>
        </div>

        <form wire:submit="checkout">
            {{$this->form}}
            <x-filament::button type="submit" class="w-full h-12 bg-primary mt-6 text-white py-2 rounded-lg">Checkout
            </x-filament::button>
        </form>

        <div class="flex items-center justify-between my-10">
            <input wire:model.live.debounce.300ms='search' type="text" placeholder="Cari produk ..."
                class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
            <input wire:model.live='barcode' type="text" placeholder="Scan dengan alat scanner ..." autofocus
                id="barcode"
                class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white ml-2">

            <x-filament::button x-data="" x-on:click="$dispatch('toggle-scanner')"
                class="px-2 w-20 h-12 bg-black text-white rounded-lg ml-2"><i class="fa fa-barcode"
                    style="font-size:36px"></i>
            </x-filament::button>

            {{-- MODAL SCAN CAMERA --}}
            <livewire:scanner-modal-component>
        </div>

        <!-- Navigation Tabs -->
        <div class="mt-5 px-2.5 overflow-x-auto hide-scrollbar">
            <div class="flex gap-2.5 pb-2.5 whitespace-nowrap">
                @foreach($categories as $item)
                <button wire:click="setCategory({{ $item['id'] ?? null }})"
                    class="category-btn px-6 py-2 mb-4 border-2 border-primary-600 rounded-lg transition-colors duration-300 {{ $selectedCategory === $item['id'] ? 'bg-primary-600 text-white' : 'dark:bg-gray-600 dark:text-white text-primary-600' }} hover:bg-primary-100">
                    {{$item['name']}}
                </button>
                @endforeach
            </div>
        </div>

        <div class="mt-5 px-2.5 overflow-x-auto hide-scrollbar">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                @foreach($products as $item)
                <div wire:click="addToOrder({{$item->id}})"
                    class="bg-white dark:bg-gray-700 p-2 rounded-lg border dark:border-none shadow cursor-pointer">
                    <img src="{{$item->image_url}}" alt="Product Image"
                        class="w-full h-24 object-cover shadow  border rounded-lg mb-2">
                    <h3 class="text-sm font-semibold">{{$item->name}}</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Rp. {{number_format($item->price, 0, ',',
                        '.')}}</p>
                </div>
                @endforeach
            </div>
        </div>
        <div class="py-4">
            {{ $products->links() }}
        </div>
    </div>

    <div class="md:col-span-1 bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 block md:hidden">
        <button wire:click="resetOrder"
            class="w-full h-12 bg-red-500 mt-2 text-white py-2 rounded-lg mb-4 ">Reset</button>
        @foreach($order_items as $item)
        <div class="mb-4 ">
            <div class="flex justify-between items-center bg-gray-100 dark:bg-gray-700 p-4 rounded-lg shadow">
                <div class="flex items-center">
                    <img src="{{$item['image_url']}}" alt="Product Image"
                        class="w-10 h-10 object-cover rounded-lg mr-2">
                    <div class="px-2">
                        <h3 class="text-sm font-semibold">{{$item['name']}}</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-xs">Rp {{number_format($item['price'], 0, ',',
                            '.')}}</p>
                    </div>
                </div>
                <div class="flex items-center">
                    <x-filament::button color="warning" wire:click="decreaseQuantity({{$item['product_id']}})">-
                    </x-filament::button>
                    <span class="px-4">{{$item['quantity']}}</span>
                    <x-filament::button color="success" wire:click="increaseQuantity({{$item['product_id']}})">+
                    </x-filament::button>
                </div>
            </div>
        </div>
        @endforeach

        @if(count($order_items) > 0)
        <div class="py-4 border-t border-gray-100 bg-gray-50 dark:bg-gray-700">
            <h3 class="text-lg font-semibold text-center mb-2">Total: Rp {{number_format($this->calculateTotal(), 0, ',',
                '.')}}</h3>
            
            @if($paid_amount > 0 && $payment_method_id)
                @php
                    $paymentMethod = \App\Models\PaymentMethod::find($payment_method_id);
                @endphp
                @if($paymentMethod && $paymentMethod->is_cash)
                <div class="text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Dibayar: Rp {{number_format($paid_amount, 0, ',', '.')}}</p>
                    <p class="text-sm font-semibold {{ $change_amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        Kembalian: Rp {{number_format($change_amount, 0, ',', '.')}}
                    </p>
                </div>
                @endif
            @endif
        </div>
        @endif
    </div>

    <div class="md:col-span-1 bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 hidden md:block">
        <button wire:click="resetOrder"
            class="w-full h-12 bg-red-500 mt-2 text-white py-2 rounded-lg mb-4">Reset</button>
        @foreach($order_items as $item)
        <div class="mb-4 ">
            <div class="flex justify-between items-center bg-gray-100 dark:bg-gray-700 p-4 rounded-lg shadow">
                <div class="flex items-center">
                    <img src="{{$item['image_url']}}" alt="Product Image"
                        class="w-10 h-10 object-cover rounded-lg mr-2">
                    <div class="px-2">
                        <h3 class="text-sm font-semibold">{{$item['name']}}</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-xs">Rp {{number_format($item['price'], 0, ',',
                            '.')}}</p>
                    </div>
                </div>
                <div class="flex items-center">
                    <x-filament::button color="warning" wire:click="decreaseQuantity({{$item['product_id']}})">-
                    </x-filament::button>
                    <span class="px-4">{{$item['quantity']}}</span>
                    <x-filament::button color="success" wire:click="increaseQuantity({{$item['product_id']}})">+
                    </x-filament::button>
                </div>
            </div>
        </div>
        @endforeach

        @if(count($order_items) > 0)
        <div class="py-4 border-t border-gray-100 bg-gray-50 dark:bg-gray-700">
            <h3 class="text-lg font-semibold text-center mb-2">Total: Rp {{number_format($this->calculateTotal(), 0, ',',
                '.')}}</h3>
            
            @if($paid_amount > 0 && $payment_method_id)
                <div class="text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Dibayar: Rp {{number_format($paid_amount, 0, ',', '.')}}</p>
                    <p class="text-sm font-semibold {{ $change_amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        Kembalian: Rp {{number_format($change_amount, 0, ',', '.')}}
                    </p>
                </div>
            @endif
        </div>
        @endif

        <div class="mt-2">
        </div>
    </div>
    <div>
        @if ($showConfirmationModal)
        <div class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50">
            <!-- Modal Content -->
            <div class="bg-white rounded-lg shadow-lg w-11/12 sm:w-96">
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-purple-500 text-white rounded-t-lg">
                    <h2 class="text-xl text-center font-semibold">PRINT STRUK</h2>
                </div>
                <!-- Modal Body -->
                <div class="px-6 py-4">
                    <p class="text-gray-800 mb-4">
                        Apakah Anda ingin mencetak struk untuk pesanan ini?
                    </p>
                    
                    @if($paid_amount > 0 && $change_amount >= 0)
                    @php
                        $paymentMethod = \App\Models\PaymentMethod::find($payment_method_id);
                    @endphp
                    @if($paymentMethod && $paymentMethod->is_cash)
                    <div class="bg-gray-100 p-3 rounded-lg mb-4">
                        <div class="text-sm space-y-1">
                            <div class="flex justify-between">
                                <span>Total:</span>
                                <span class="font-semibold">Rp {{number_format($this->calculateTotal(), 0, ',', '.')}}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Dibayar:</span>
                                <span>Rp {{number_format($paid_amount, 0, ',', '.')}}</span>
                            </div>
                            <div class="flex justify-between border-t pt-1">
                                <span>Kembalian:</span>
                                <span class="font-semibold text-green-600">Rp {{number_format($change_amount, 0, ',', '.')}}</span>
                            </div>
                        </div>
                    </div>
                    @endif
                    @endif
                </div>
                <!-- Modal Footer -->
                <div class="px-6 py-4 flex justify-center space-x-4">
                    <button wire:click="$set('showConfirmationModal', false)"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-full hover:bg-gray-400 focus:ring-2 focus:ring-gray-500">
                        Tidak
                    </button>
                    @if ($print_via_mobile == true)
                    <button wire:click="printBluetooth"
                        class="px-4 py-2 bg-purple-500 text-white rounded-full hover:bg-blue-600 focus:ring-2 focus:ring-blue-400">
                        Cetak
                    </button>
                    @else
                    <button wire:click="printLocalKabel"
                        class="px-4 py-2 bg-purple-500 text-white rounded-full hover:bg-blue-600 focus:ring-2 focus:ring-blue-400">
                        Cetak
                    </button>

                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Transaction Modal -->
    @if($showTransactionModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-11/12 sm:w-2/3 md:w-1/2 lg:w-2/5 max-h-[90vh] overflow-y-auto">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ $this->getTransactionTypeTitle() }}
                    </h2>
                    <button wire:click="closeTransactionModal" 
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="px-6 py-4">
                <form wire:submit="saveTransaction" class="space-y-4">
                    @if($transactionType === 'transfer')
                        <!-- Sumber Dana -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <i class="fas fa-wallet mr-2 text-blue-500"></i>Sumber Dana
                            </label>
                            <select wire:model="transactionData.sumber_dana_id" 
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white bg-gray-50 dark:bg-gray-800 transition-all duration-200"
                                required>
                                <option value="">-- Pilih Sumber Dana --</option>
                                @foreach($payment_methods as $pm)
                                    <option value="{{ $pm->id }}">{{ $pm->name }} (Saldo: Rp {{ number_format($pm->balance, 0, ',', '.') }})</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Jumlah Transfer -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <i class="fas fa-money-bill-wave mr-2 text-green-500"></i>Jumlah Transfer
                            </label>
                            <div class="relative">
                                <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">
                                    Rp
                                </div>
                                <input type="text" 
                                    wire:model="transactionData.amount"
                                    class="w-full pl-12 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white bg-gray-50 dark:bg-gray-800 transition-all duration-200 text-right font-medium"
                                    required 
                                    placeholder="Masukkan jumlah transfer"
                                    >
                            </div>
                        </div>
                        
                        <!-- Admin Luar -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <i class="fas fa-receipt mr-2 text-orange-500"></i>Admin Luar (ke Cash)
                            </label>
                            <div class="relative">
                                <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">
                                    Rp
                                </div>
                                <input type="text" 
                                    wire:model="transactionData.admin_luar"
                                    class="w-full pl-12 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white bg-gray-50 dark:bg-gray-800 transition-all duration-200 text-right font-medium"
                                    placeholder=""
                                    >
                            </div>
                        </div>
                        
                        <!-- Admin Dalam -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <i class="fas fa-percentage mr-2 text-purple-500"></i>Admin Dalam (ke Sumber Dana)
                            </label>
                            <div class="relative">
                                <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">
                                    Rp
                                </div>
                                <input type="text" 
                                    wire:model="transactionData.admin_dalam"
                                    class="w-full pl-12 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white bg-gray-50 dark:bg-gray-800 transition-all duration-200 text-right font-medium"
                                    placeholder=""
                                    >
                            </div>
                        </div>
                        
                        <!-- Keterangan -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <i class="fas fa-sticky-note mr-2 text-gray-500"></i>Keterangan
                            </label>
                            <textarea wire:model="transactionData.keterangan"
                                rows="3"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white bg-gray-50 dark:bg-gray-800 transition-all duration-200 resize-none"
                                placeholder="Keterangan tambahan (opsional)"></textarea>
                        </div>
                        
                    @elseif($transactionType === 'tarik_tunai')
                        <!-- Sumber Dana -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <i class="fas fa-wallet mr-2 text-blue-500"></i>Sumber Dana
                            </label>
                            <select wire:model="transactionData.sumber_dana_id" 
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white bg-gray-50 dark:bg-gray-800 transition-all duration-200"
                                required>
                                <option value="">-- Pilih Sumber Dana --</option>
                                @foreach($payment_methods as $pm)
                                    <option value="{{ $pm->id }}">{{ $pm->name }} (Saldo: Rp {{ number_format($pm->balance, 0, ',', '.') }})</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Jumlah -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <i class="fas fa-money-bill-wave mr-2 text-green-500"></i>Jumlah Penarikan
                            </label>
                            <div class="relative">
                                <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">
                                    Rp
                                </div>
                                <input type="text" 
                                    wire:model="transactionData.amount"
                                    class="w-full pl-12 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white bg-gray-50 dark:bg-gray-800 transition-all duration-200 text-right font-medium"
                                    required 
                                    placeholder="Masukkan jumlah penarikan"
                                    >
                            </div>
                        </div>
                        
                        <!-- Tujuan Penarikan -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <i class="fas fa-map-marker-alt mr-2 text-red-500"></i>Tujuan Penarikan
                            </label>
                            <select wire:model="transactionData.tujuan_dana_id" 
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white bg-gray-50 dark:bg-gray-800 transition-all duration-200"
                                required>
                                <option value="">-- Pilih Tujuan Penarikan --</option>
                                @foreach($payment_methods as $pm)
                                    <option value="{{ $pm->id }}">{{ $pm->name }} (Saldo: Rp {{ number_format($pm->balance, 0, ',', '.') }})</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                <i class="fas fa-info-circle mr-1"></i>
                                Saldo tujuan akan bertambah sesuai jumlah penarikan
                            </p>
                        </div>
                        
                        <!-- Admin Luar -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <i class="fas fa-receipt mr-2 text-orange-500"></i>Admin Luar (ke Cash)
                            </label>
                            <div class="relative">
                                <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">
                                    Rp
                                </div>
                                <input type="text" 
                                    wire:model="transactionData.admin_luar"
                                    class="w-full pl-12 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white bg-gray-50 dark:bg-gray-800 transition-all duration-200 text-right font-medium"
                                    placeholder=""
                                    >
                            </div>
                        </div>
                        
                        <!-- Admin Dalam -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <i class="fas fa-percentage mr-2 text-purple-500"></i>Admin Dalam (ke Sumber Dana)
                            </label>
                            <div class="relative">
                                <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">
                                    Rp
                                </div>
                                <input type="text" 
                                    wire:model="transactionData.admin_dalam"
                                    class="w-full pl-12 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white bg-gray-50 dark:bg-gray-800 transition-all duration-200 text-right font-medium"
                                    placeholder=""
                                    >
                            </div>
                        </div>
                        
                        <!-- Keterangan -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <i class="fas fa-sticky-note mr-2 text-gray-500"></i>Keterangan
                            </label>
                            <textarea wire:model="transactionData.keterangan"
                                rows="3"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white bg-gray-50 dark:bg-gray-800 transition-all duration-200 resize-none"
                                placeholder="Keterangan tambahan (opsional)"></textarea>
                        </div>
                        
                    @elseif($transactionType === 'jasa_transfer')
                        <!-- Info Box -->
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-4 rounded-lg mb-4 border border-blue-200 dark:border-blue-800">
                            <div class="flex items-center">
                                <i class="fas fa-info-circle text-blue-500 text-lg mr-3"></i>
                                <div>
                                    <p class="text-sm font-medium text-blue-800 dark:text-blue-300">
                                        Sumber Dana Otomatis: Cash
                                    </p>
                                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                        Saldo cash akan dikurangi sebesar total (jumlah + admin)
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Jumlah Transfer -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <i class="fas fa-exchange-alt mr-2 text-green-500"></i>Jumlah Transfer
                            </label>
                            <div class="relative">
                                <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">
                                    Rp
                                </div>
                                <input type="text" 
                                    wire:model="transactionData.terima_dana"
                                    class="w-full pl-12 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white bg-gray-50 dark:bg-gray-800 transition-all duration-200 text-right font-medium"
                                    required 
                                    placeholder="Masukkan jumlah yang akan ditransfer"
                                    >
                            </div>
                        </div>
                        
                        <!-- Biaya Admin -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <i class="fas fa-percentage mr-2 text-orange-500"></i>Biaya Admin
                            </label>
                            <div class="relative">
                                <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">
                                    Rp
                                </div>
                                <input type="text" 
                                    wire:model="transactionData.admin"
                                    class="w-full pl-12 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white bg-gray-50 dark:bg-gray-800 transition-all duration-200 text-right font-medium"
                                    placeholder=""
                                    >
                            </div>
                        </div>
                        
                        @php
                            $jumlah = (float)($transactionData['terima_dana'] ?? 0);
                            $admin = (float)($transactionData['admin'] ?? 0);
                            $total = $jumlah + $admin;
                        @endphp
                        
                        @if($total > 0)
                        <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                            <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3 flex items-center">
                                <i class="fas fa-calculator mr-2 text-blue-500"></i>Ringkasan Perhitungan
                            </h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 dark:text-gray-400">Jumlah Transfer:</span>
                                    <span class="font-medium text-green-600 dark:text-green-400">+ Rp {{ number_format($jumlah, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 dark:text-gray-400">Biaya Admin:</span>
                                    <span class="font-medium text-green-600 dark:text-green-400">+ Rp {{ number_format($admin, 0, ',', '.') }}</span>
                                </div>
                                <div class="border-t border-gray-300 dark:border-gray-600 pt-2 mt-2">
                                    <div class="flex justify-between items-center">
                                        <span class="font-semibold text-gray-800 dark:text-gray-200">Total Dikurangi dari Cash:</span>
                                        <span class="font-bold text-red-600 dark:text-red-400 text-lg">- Rp {{ number_format($total, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Keterangan -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <i class="fas fa-sticky-note mr-2 text-gray-500"></i>Keterangan
                            </label>
                            <textarea wire:model="transactionData.keterangan"
                                rows="3"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white bg-gray-50 dark:bg-gray-800 transition-all duration-200 resize-none"
                                placeholder="Keterangan tambahan (opsional)"></textarea>
                        </div>
                        
                    @elseif($transactionType === 'mode_pulsa')
                        <!-- Sumber Dana -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <i class="fas fa-wallet mr-2 text-blue-500"></i>Sumber Dana
                            </label>
                            <select wire:model="transactionData.sumber_dana_id" 
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white bg-gray-50 dark:bg-gray-800 transition-all duration-200">
                                <option value="">Cash (Default)</option>
                                @foreach($payment_methods as $pm)
                                    <option value="{{ $pm->id }}">{{ $pm->name }} (Saldo: Rp {{ number_format($pm->balance, 0, ',', '.') }})</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                <i class="fas fa-info-circle mr-1"></i>
                                Jika tidak dipilih, akan menggunakan Cash sebagai sumber dana
                            </p>
                        </div>
                        
                        <!-- Jenis Transaksi -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <i class="fas fa-tags mr-2 text-purple-500"></i>Jenis Transaksi
                            </label>
                            <input type="text" 
                                wire:model="transactionData.jenis_transaksi"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white bg-gray-50 dark:bg-gray-800 transition-all duration-200"
                                required
                                placeholder="Contoh: Pulsa Telkomsel 20k, Token Listrik 50k, dll">
                        </div>
                        
                        <!-- Sumber -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <i class="fas fa-server mr-2 text-indigo-500"></i>Sumber
                            </label>
                            <input type="text" 
                                wire:model="transactionData.sumber"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white bg-gray-50 dark:bg-gray-800 transition-all duration-200"
                                required
                                placeholder="Contoh: Server A, Agen B, dll">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Modal -->
                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    <i class="fas fa-money-bill mr-2 text-red-500"></i>Modal
                                </label>
                                <div class="relative">
                                    <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">
                                        Rp
                                    </div>
                                    <input type="text" 
                                        wire:model="transactionData.modal"
                                        class="w-full pl-12 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white bg-gray-50 dark:bg-gray-800 transition-all duration-200 text-right font-medium"
                                        required 
                                        placeholder="Masukkan harga modal/beli"
                                        >
                                </div>
                            </div>
                            
                            <!-- Harga Jual -->
                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    <i class="fas fa-tag mr-2 text-green-500"></i>Harga Jual
                                </label>
                                <div class="relative">
                                    <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">
                                        Rp
                                    </div>
                                    <input type="text" 
                                        wire:model="transactionData.harga_jual"
                                        class="w-full pl-12 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white bg-gray-50 dark:bg-gray-800 transition-all duration-200 text-right font-medium"
                                        required 
                                        placeholder="Masukkan harga jual ke customer"
                                        >
                                </div>
                            </div>
                        </div>
                        
                        <!-- Biaya Admin -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <i class="fas fa-percentage mr-2 text-orange-500"></i>Biaya Admin
                            </label>
                            <div class="relative">
                                <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">
                                    Rp
                                </div>
                                <input type="text" 
                                    wire:model="transactionData.admin"
                                    class="w-full pl-12 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white bg-gray-50 dark:bg-gray-800 transition-all duration-200 text-right font-medium"
                                    placeholder=""
                                    >
                            </div>
                        </div>
                        
                        @php
                            $modal = (float)($transactionData['modal'] ?? 0);
                            $hargaJual = (float)($transactionData['harga_jual'] ?? 0);
                            $adminPulsa = (float)($transactionData['admin'] ?? 0);
                            $profit = $hargaJual - $modal + $adminPulsa;
                        @endphp
                        
                        @if($modal > 0 || $hargaJual > 0 || $adminPulsa > 0)
                        <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                            <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3 flex items-center">
                                <i class="fas fa-calculator mr-2 text-blue-500"></i>Ringkasan Perhitungan Profit
                            </h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 dark:text-gray-400">Modal (Pengeluaran):</span>
                                    <span class="font-medium text-red-600 dark:text-red-400">- Rp {{ number_format($modal, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 dark:text-gray-400">Harga Jual (Pemasukan):</span>
                                    <span class="font-medium text-green-600 dark:text-green-400">+ Rp {{ number_format($hargaJual, 0, ',', '.') }}</span>
                                </div>
                                @if($adminPulsa > 0)
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 dark:text-gray-400">Biaya Admin (Pemasukan):</span>
                                    <span class="font-medium text-green-600 dark:text-green-400">+ Rp {{ number_format($adminPulsa, 0, ',', '.') }}</span>
                                </div>
                                @endif
                                <div class="border-t border-gray-300 dark:border-gray-600 pt-2 mt-2">
                                    <div class="flex justify-between items-center">
                                        <span class="font-semibold text-gray-800 dark:text-gray-200">Total Profit:</span>
                                        <span class="font-bold text-lg {{ $profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $profit >= 0 ? '+' : '' }} Rp {{ number_format(abs($profit), 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Keterangan -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <i class="fas fa-sticky-note mr-2 text-gray-500"></i>Keterangan
                            </label>
                            <textarea wire:model="transactionData.keterangan"
                                rows="3"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white bg-gray-50 dark:bg-gray-800 transition-all duration-200 resize-none"
                                placeholder="Keterangan tambahan (opsional)"></textarea>
                        </div>
                    @endif
                    
                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-600 mt-6">
                        <button type="button" wire:click="closeTransactionModal"
                            class="px-6 py-3 bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-all duration-200 font-medium">
                            <i class="fas fa-times mr-2"></i>Batal
                        </button>
                        <button type="submit"
                            class="px-8 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200 font-medium shadow-lg">
                            <i class="fas fa-save mr-2"></i>Simpan Transaksi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>