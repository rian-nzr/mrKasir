<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pilih Toko - {{ config('app.name') }}</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            200: '#99f6e4',
                            300: '#5eead4',
                            400: '#2dd4bf',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .store-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .store-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .btn-select {
            transition: all 0.2s ease-in-out;
        }
        .btn-select:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(13, 148, 136, 0.4);
        }
        .header-card {
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 50%, #2dd4bf 100%);
        }
    </style>
</head>
<body class="h-full bg-gray-50">
    <div class="min-h-screen flex flex-col justify-center py-8 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-7xl mx-auto">
            
            <!-- Header Section -->
            <div class="text-center mb-12">
                <div class="header-card inline-flex items-center justify-center w-20 h-20 rounded-full mb-6 shadow-xl">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <h1 class="text-5xl font-bold text-gray-900 mb-4 tracking-tight">Pilih Toko</h1>
                <div class="max-w-2xl mx-auto">
                    <p class="text-xl text-gray-700 leading-relaxed">
                        Selamat datang, <span class="font-semibold text-primary-600 bg-primary-50 px-3 py-1 rounded-full">{{ Auth::user()->name }}</span>
                    </p>
                    <p class="text-gray-600 mt-2">Silakan pilih toko yang ingin Anda kelola</p>
                </div>
            </div>

            <!-- Store Selection Container -->
            <div class="bg-white rounded-3xl shadow-2xl p-8 lg:p-12 border border-gray-100">
                <form action="{{ route('store.select') }}" method="POST">
                    @csrf
                    
                    @if($stores->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
                            @foreach($stores as $store)
                            <div class="store-card group">
                                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 h-full hover:border-primary-200 hover:shadow-2xl transition-all duration-300">
                                    <!-- Store Icon Circle -->
                                    <div class="flex justify-center mb-6">
                                        <div class="relative">
                                            <div class="w-20 h-20 bg-gradient-to-br from-primary-100 to-primary-200 rounded-full flex items-center justify-center group-hover:from-primary-200 group-hover:to-primary-300 transition-all duration-300 shadow-lg">
                                                <svg class="w-10 h-10 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                </svg>
                                            </div>
                                            <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-green-500 rounded-full border-2 border-white flex items-center justify-center">
                                                <div class="w-2 h-2 bg-white rounded-full"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Store Information -->
                                    <div class="text-center space-y-4 mb-8">
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $store->name }}</h3>
                                            <div class="inline-flex items-center px-4 py-2 bg-primary-50 text-primary-700 rounded-full text-sm font-semibold border border-primary-100">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                                </svg>
                                                {{ $store->code }}
                                            </div>
                                        </div>
                                        
                                        <div class="space-y-3">
                                            @if($store->address)
                                            <div class="flex items-start justify-center text-gray-600 group-hover:text-gray-700 transition-colors">
                                                <svg class="w-5 h-5 mr-3 mt-0.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                </svg>
                                                <span class="text-sm leading-relaxed">{{ Str::limit($store->address, 45) }}</span>
                                            </div>
                                            @endif
                                            
                                            @if($store->phone)
                                            <div class="flex items-center justify-center text-gray-600 group-hover:text-gray-700 transition-colors">
                                                <svg class="w-5 h-5 mr-3 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                                </svg>
                                                <span class="text-sm font-medium">{{ $store->phone }}</span>
                                            </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Select Button -->
                                    <button type="submit" name="store_id" value="{{ $store->id }}" 
                                            class="btn-select w-full bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white py-4 px-6 rounded-xl font-semibold text-lg shadow-lg hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-primary-300 focus:ring-opacity-50 transition-all duration-300">
                                        <span class="flex items-center justify-center">
                                            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Pilih Toko Ini
                                        </span>
                                    </button>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <!-- Empty State -->
                        <div class="text-center py-16">
                            <div class="inline-flex items-center justify-center w-24 h-24 bg-gray-100 rounded-full mb-6">
                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">Tidak ada toko tersedia</h3>
                            <p class="text-gray-600 max-w-md mx-auto">Silakan hubungi administrator untuk menambahkan toko baru ke sistem.</p>
                        </div>
                    @endif
                </form>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8">
                <a href="{{ route('filament.admin.auth.logout') }}" 
                   class="inline-flex items-center px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 hover:text-gray-900 rounded-full font-medium transition-all duration-300 border border-gray-200 hover:border-gray-300">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    Logout
                </a>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    @if(session('success'))
    <div id="success-toast" class="fixed top-6 right-6 bg-green-600 text-white px-6 py-4 rounded-xl shadow-lg z-50 flex items-center space-x-3 max-w-sm">
        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span class="font-medium">{{ session('success') }}</span>
    </div>
    <script>
        setTimeout(() => {
            const toast = document.getElementById('success-toast');
            if (toast) {
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }
        }, 3000);
    </script>
    @endif

    @if($errors->any())
    <div id="error-toast" class="fixed top-6 right-6 bg-red-600 text-white px-6 py-4 rounded-xl shadow-lg z-50 flex items-center space-x-3 max-w-sm">
        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div>
            @foreach($errors->all() as $error)
            <div class="font-medium">{{ $error }}</div>
            @endforeach
        </div>
    </div>
    <script>
        setTimeout(() => {
            const toast = document.getElementById('error-toast');
            if (toast) {
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }
        }, 5000);
    </script>
    @endif
</body>
</html>
