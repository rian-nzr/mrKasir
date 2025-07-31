<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Toko - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
            <div class="text-center mb-8">
                <div class="mx-auto w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">
                    Selamat Datang, Super Admin!
                </h1>
                <p class="text-gray-600">
                    Pilih toko yang akan Anda kelola untuk melanjutkan ke dashboard.
                </p>
            </div>

            <form action="{{ route('admin.store-selection.select') }}" method="POST" class="space-y-6">
                @csrf
                <div>
                    <label for="store_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Pilih Toko
                    </label>
                    <select name="store_id" id="store_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Pilih Toko --</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }} ({{ $store->code }})</option>
                        @endforeach
                    </select>
                    @error('store_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <button type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md transition duration-300">
                    Pilih Toko & Lanjutkan
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <a href="{{ route('store.change') }}" class="text-sm text-gray-600 hover:text-gray-800">
                    Perlu mengganti toko? Klik di sini
                </a>
            </div>
        </div>
    </div>
</body>
</html>
