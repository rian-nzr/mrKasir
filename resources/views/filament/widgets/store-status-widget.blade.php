<div class="fi-wi-store-status p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="flex items-center space-x-2">
                <x-heroicon-o-building-storefront class="w-6 h-6 text-primary-600" />
                <div>
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        Toko Aktif
                    </div>
                    @if($currentStore)
                        <div class="text-lg font-bold text-primary-600 dark:text-primary-400">
                            {{ $currentStore->name }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $currentStore->code }} • {{ $currentStore->address }}
                        </div>
                    @else
                        <div class="text-lg font-bold text-red-600 dark:text-red-400">
                            Tidak ada toko dipilih
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        @if($isSuperAdmin && $stores->count() > 0)
            <div class="flex items-center space-x-2">
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Ganti Toko:
                </div>
                <div class="flex space-x-1">
                    @foreach($stores as $store)
                        <button 
                            wire:click="selectStore({{ $store->id }})"
                            class="px-3 py-1 text-xs rounded-full transition-colors duration-200
                                {{ $currentStore && $currentStore->id === $store->id 
                                    ? 'bg-primary-600 text-white' 
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-primary-100 dark:hover:bg-primary-900' 
                                }}"
                            title="{{ $store->name }} - {{ $store->address }}"
                        >
                            {{ $store->code }}
                        </button>
                    @endforeach
                </div>
                <a href="{{ route('store.select') }}" 
                   class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-200 underline">
                    Kelola
                </a>
            </div>
        @endif
    </div>
    
    @if($currentStore)
        <div class="mt-3 grid grid-cols-3 gap-4 text-center">
            <div class="p-2 bg-gray-50 dark:bg-gray-700 rounded">
                <div class="text-xs text-gray-500 dark:text-gray-400">Total Produk</div>
                <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    {{ $currentStore->products()->count() }}
                </div>
            </div>
            <div class="p-2 bg-gray-50 dark:bg-gray-700 rounded">
                <div class="text-xs text-gray-500 dark:text-gray-400">Total Order Hari Ini</div>
                <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    {{ $currentStore->orders()->whereDate('created_at', today())->count() }}
                </div>
            </div>
            <div class="p-2 bg-gray-50 dark:bg-gray-700 rounded">
                <div class="text-xs text-gray-500 dark:text-gray-400">User Toko</div>
                <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    {{ $currentStore->users()->count() }}
                </div>
            </div>
        </div>
    @endif
</div>
