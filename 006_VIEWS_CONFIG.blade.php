{{-- 
  BalanceBoat - Blade View Files
  Copy these files to resources/views/ directory
--}}

{{-- ================================================================
   LAYOUT: Base Layout - resources/views/layouts/app.blade.php
================================================================ --}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'BalanceBoat - Center Dashboard')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex">
        {{-- Sidebar --}}
        @include('components.sidebar')
        
        {{-- Main Content --}}
        <div class="flex-1 flex flex-col overflow-hidden ml-72">
            {{-- Top Bar --}}
            @include('components.topbar')
            
            {{-- Content --}}
            <main class="flex-1 overflow-y-auto">
                <div class="px-8 py-6">
                    {{-- Alerts --}}
                    @if ($errors->any())
                        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    @if (session('success'))
                        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                            {{ session('success') }}
                        </div>
                    @endif
                    
                    @yield('main-content')
                </div>
            </main>
        </div>
    </div>

    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    @stack('scripts')
</body>
</html>

{{-- ================================================================
   SIDEBAR COMPONENT - resources/views/components/sidebar.blade.php
================================================================ --}}

<aside class="fixed left-0 top-0 h-full w-72 bg-gradient-to-b from-purple-50 via-pink-50 to-green-50 border-r border-purple-200 flex flex-col">
    {{-- Logo --}}
    <div class="px-6 py-6 border-b border-purple-200">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center text-white font-bold text-lg">
                🧘
            </div>
            <span class="text-lg font-bold bg-gradient-to-r from-purple-700 to-pink-600 bg-clip-text text-transparent">
                BalanceBoat
            </span>
        </a>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 px-4 py-6 space-y-1">
        <a href="{{ route('dashboard') }}" 
           class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 font-medium text-sm transition-all duration-200 hover:bg-purple-100 @if(request()->routeIs('dashboard')) bg-purple-100 text-purple-900 @endif">
            <span class="text-xl">📊</span>
            <span>Dashboard</span>
        </a>
        
        <a href="{{ route('account.show') }}" 
           class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 font-medium text-sm transition-all duration-200 hover:bg-purple-100 @if(request()->routeIs('account.*')) bg-purple-100 text-purple-900 @endif">
            <span class="text-xl">👤</span>
            <span>Account</span>
        </a>
        
        <a href="{{ route('retreat.index') }}" 
           class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 font-medium text-sm transition-all duration-200 hover:bg-purple-100 @if(request()->routeIs('retreat.*')) bg-purple-100 text-purple-900 @endif">
            <span class="text-xl">🏛️</span>
            <span>Retreats</span>
        </a>
        
        <a href="{{ route('booking.index') }}" 
           class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 font-medium text-sm transition-all duration-200 hover:bg-purple-100 @if(request()->routeIs('booking.*')) bg-purple-100 text-purple-900 @endif">
            <span class="text-xl">📅</span>
            <span>Bookings</span>
        </a>
    </nav>

    {{-- Footer --}}
    <div class="px-4 py-4 border-t border-purple-200">
        <div class="flex items-center gap-3 p-3 bg-white rounded-lg border border-purple-200">
            <div class="w-9 h-9 bg-gradient-to-br from-green-400 to-blue-500 rounded-lg flex items-center justify-center text-white text-sm font-bold">
                {{ substr(auth()->user()->first_name, 0, 1) . substr(auth()->user()->last_name, 0, 1) }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-sm font-semibold text-gray-900 truncate">
                    {{ auth()->user()->primary_center?->name ?? 'No Center' }}
                </div>
                <div class="text-xs text-gray-500">Admin</div>
            </div>
        </div>
    </div>
</aside>

{{-- ================================================================
   TOPBAR COMPONENT - resources/views/components/topbar.blade.php
================================================================ --}}

<div class="bg-white border-b border-gray-200 px-8 py-4 flex items-center justify-between">
    <div class="flex-1"></div>
    <div class="flex items-center gap-6">
        <button class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-bell text-lg"></i>
        </button>
        <div class="relative group">
            <button class="flex items-center gap-2 text-gray-900 hover:text-purple-600">
                <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->full_name) }}" 
                     alt="Avatar" class="w-8 h-8 rounded-lg">
                <span class="text-sm font-medium">{{ auth()->user()->first_name }}</span>
            </button>
            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-10">
                <a href="{{ route('account.show') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                <form method="POST" action="{{ route('logout') }}" class="block">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ================================================================
   RETREAT INDEX - resources/views/retreat/index.blade.php
================================================================ --}}

@extends('layouts.app')

@section('main-content')
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Retreats</h1>
            <p class="text-gray-600 mt-1">Manage your wellness retreat programs</p>
        </div>
        <a href="{{ route('retreat.create') }}" class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg hover:shadow-lg transition-shadow">
            + Create Retreat
        </a>
    </div>
</div>

{{-- Stats --}}
<div class="grid grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg p-6 border border-gray-200">
        <div class="text-gray-600 text-sm font-semibold uppercase mb-2">Total Retreats</div>
        <div class="text-3xl font-bold text-gray-900">{{ $retreats->total() }}</div>
    </div>
    <div class="bg-white rounded-lg p-6 border border-gray-200">
        <div class="text-gray-600 text-sm font-semibold uppercase mb-2">Published</div>
        <div class="text-3xl font-bold text-green-600">{{ $retreats->where('is_bookable', true)->count() }}</div>
    </div>
    <div class="bg-white rounded-lg p-6 border border-gray-200">
        <div class="text-gray-600 text-sm font-semibold uppercase mb-2">Total Bookings</div>
        <div class="text-3xl font-bold text-blue-600">{{ $total_bookings }}</div>
    </div>
    <div class="bg-white rounded-lg p-6 border border-gray-200">
        <div class="text-gray-600 text-sm font-semibold uppercase mb-2">Total Revenue</div>
        <div class="text-3xl font-bold text-purple-600">₹{{ number_format($total_revenue) }}</div>
    </div>
</div>

{{-- Retreats Grid --}}
<div class="grid grid-cols-3 gap-6">
    @foreach($retreats as $retreat)
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300">
            {{-- Image --}}
            <div class="relative h-48 overflow-hidden bg-gradient-to-br from-purple-100 to-pink-100">
                @if($retreat->banner_image_url)
                    <img src="{{ asset('storage/' . $retreat->banner_image_url) }}" 
                         alt="{{ $retreat->name }}"
                         class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center text-4xl">🧘</div>
                @endif
                
                {{-- Badge --}}
                <div class="absolute top-4 right-4">
                    @if($retreat->is_draft)
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">
                            ✏️ Draft
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                            ✓ Live
                        </span>
                    @endif
                </div>

                {{-- Occupancy --}}
                <div class="absolute bottom-4 left-4 bg-white/90 backdrop-blur-sm rounded-lg px-3 py-2">
                    <div class="text-xs font-semibold text-gray-600">
                        {{ $retreat->occupied_spaces }}/{{ $retreat->total_spaces }} Booked
                    </div>
                    <div class="w-24 h-1.5 bg-gray-200 rounded-full mt-1 overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-green-500 to-blue-500" 
                             style="width: {{ $retreat->occupancy_percentage }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Content --}}
            <div class="p-5">
                <h3 class="font-bold text-gray-900 text-lg mb-1 line-clamp-2">{{ $retreat->name }}</h3>
                
                <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $retreat->experience_summary }}</p>

                <div class="grid grid-cols-2 gap-3 mb-4 text-sm">
                    <div>
                        <div class="text-gray-500 text-xs">Dates</div>
                        <div class="font-semibold text-gray-900">
                            {{ $retreat->start_date_time?->format('M d') ?? 'N/A' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-gray-500 text-xs">Price</div>
                        <div class="font-semibold text-gray-900">
                            ₹{{ number_format($retreat->price_per_person) }}
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2">
                    <a href="{{ route('retreat.edit', $retreat) }}" 
                       class="flex-1 px-3 py-2 bg-purple-50 text-purple-700 rounded-lg text-sm font-semibold hover:bg-purple-100 transition-colors">
                        Edit
                    </a>
                    <a href="{{ route('retreat.show', $retreat) }}" 
                       class="flex-1 px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-200 transition-colors">
                        View
                    </a>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Pagination --}}
<div class="mt-8">
    {{ $retreats->links() }}
</div>
@endsection

{{-- ================================================================
   CONFIG: Database Config - config/database.php (relevant section)
================================================================ --}}

{{-- 
Copy this to your Laravel config/database.php
In the 'connections' array, update the mysql section:
--}}

'mysql' => [
    'driver' => 'mysql',
    'url' => env('DATABASE_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', 3306),
    'database' => env('DB_DATABASE', 'balanceboat'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'unix_socket' => env('DB_SOCKET', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => null,
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
    ]) : [],
],

{{-- ================================================================
   ENV EXAMPLE - .env.example
================================================================ --}}

APP_NAME=BalanceBoat
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://dashboard.balanceboat.com

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=balanceboat_center
DB_USERNAME=balanceboat
DB_PASSWORD=secure_password

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=public
QUEUE_CONNECTION=redis
SESSION_DRIVER=cookie

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@balanceboat.com
MAIL_FROM_NAME="BalanceBoat"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=

STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...

MEILISEARCH_HOST=http://localhost:7700
MEILISEARCH_KEY=

TELESCOPE_ENABLED=false

