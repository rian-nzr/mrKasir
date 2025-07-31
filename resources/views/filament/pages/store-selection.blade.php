<x-filament-panels::page>
    <div class="max-w-md mx-auto">
        <div class="text-center mb-8">
            <div class="mx-auto w-24 h-24 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center mb-4">
                <x-heroicon-o-building-storefront class="w-12 h-12 text-primary-600 dark:text-primary-400" />
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                Selamat Datang, Super Admin!
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Pilih toko yang akan Anda kelola untuk melanjutkan ke dashboard.
            </p>
        </div>

        <form wire:submit="selectStore" class="space-y-6">
            {{ $this->form }}
            
            <div class="text-center space-y-4">
                {{ $this->getFormActions()[0] }}
                
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    <p>💡 Tip: Anda dapat mengubah toko yang dipilih kapan saja melalui menu profil</p>
                </div>
            </div>
        </form>

        <div class="mt-8 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <div class="flex items-start">
                <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3 flex-shrink-0" />
                <div class="text-sm text-blue-800 dark:text-blue-200">
                    <p class="font-medium mb-1">Informasi Penting:</p>
                    <ul class="list-disc list-inside space-y-1 text-blue-700 dark:text-blue-300">
                        <li>Data yang ditampilkan akan disesuaikan dengan toko yang dipilih</li>
                        <li>Laporan dan statistik akan menampilkan data toko yang sedang aktif</li>
                        <li>Anda dapat beralih toko kapan saja tanpa perlu logout</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
