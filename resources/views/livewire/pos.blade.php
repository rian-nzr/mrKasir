<div class="grid grid-cols-1 md:grid-cols-3 gap-4" style="font-family : poppins;">
    <div class="md:col-span-2">

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
        <div class="py-4 ">
            <h3 class="text-lg font-semibold text-center">Total: Rp {{number_format($this->calculateTotal(), 0, ',',
                '.')}}</h3>
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
        <div class="py-4 border-t border-gray-100 bg-gray-50 dark:bg-gray-700 ">
            <h3 class="text-lg font-semibold text-center">Total: Rp {{number_format($this->calculateTotal(), 0, ',',
                '.')}}</h3>
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
                    <p class="text-gray-800">
                        Apakah Anda ingin mencetak struk untuk pesanan ini?
                    </p>
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
</div>