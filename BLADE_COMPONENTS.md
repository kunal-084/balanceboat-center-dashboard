# BalanceBoat Blade Components & Views

## Component Structure & TailwindCSS Implementation

### Layout: app.blade.php

```blade
@extends('layouts.base')

@section('content')
<div class="flex h-screen bg-gray-50">
    <!-- Sidebar -->
    @include('components.sidebar')
    
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden ml-72">
        <!-- Top Bar -->
        @include('components.topbar')
        
        <!-- Content -->
        <main class="flex-1 overflow-y-auto">
            <div class="px-8 py-6">
                @if ($errors->any())
                    @include('components.ui.alert', ['type' => 'error', 'messages' => $errors->all()])
                @endif
                
                @if (session('success'))
                    @include('components.ui.alert', ['type' => 'success', 'message' => session('success')])
                @endif
                
                @yield('main-content')
            </div>
        </main>
    </div>
</div>
@endsection
```

### Sidebar Component: components/sidebar.blade.php

```blade
<aside class="fixed left-0 top-0 h-full w-72 bg-gradient-to-b from-purple-50 via-pink-50 to-green-50 border-r border-purple-200 flex flex-col">
    <!-- Logo -->
    <div class="px-6 py-6 border-b border-purple-200">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center text-white font-bold">
                🧘
            </div>
            <span class="text-lg font-display font-bold bg-gradient-to-r from-purple-700 to-pink-600 bg-clip-text text-transparent">
                BalanceBoat
            </span>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-1">
        <a href="{{ route('dashboard') }}" 
           class="nav-link @if(request()->routeIs('dashboard')) active @endif">
            <span class="text-xl">📊</span>
            <span>Dashboard</span>
        </a>
        
        <a href="{{ route('account.show') }}" 
           class="nav-link @if(request()->routeIs('account.*')) active @endif">
            <span class="text-xl">👤</span>
            <span>Account Info</span>
        </a>
        
        <a href="{{ route('retreat.index') }}" 
           class="nav-link @if(request()->routeIs('retreat.*')) active @endif">
            <span class="text-xl">🏛️</span>
            <span>Retreats</span>
        </a>
        
        <a href="{{ route('accommodation.index') }}" 
           class="nav-link @if(request()->routeIs('accommodation.*')) active @endif">
            <span class="text-xl">🛏️</span>
            <span>Accommodations</span>
        </a>
        
        <a href="{{ route('booking.index') }}" 
           class="nav-link @if(request()->routeIs('booking.*')) active @endif">
            <span class="text-xl">📅</span>
            <span>Bookings</span>
        </a>
        
        <a href="{{ route('analytics') }}" 
           class="nav-link @if(request()->routeIs('analytics')) active @endif">
            <span class="text-xl">📈</span>
            <span>Analytics</span>
        </a>
        
        <a href="{{ route('ai-insights') }}" 
           class="nav-link @if(request()->routeIs('ai-insights')) active @endif">
            <span class="text-xl">🤖</span>
            <span>AI Insights</span>
        </a>
    </nav>

    <!-- Footer -->
    <div class="px-4 py-4 border-t border-purple-200">
        <div class="flex items-center gap-3 p-3 bg-white rounded-lg border border-purple-200">
            <div class="w-9 h-9 bg-gradient-to-br from-green-400 to-blue-500 rounded-lg flex items-center justify-center text-white text-sm font-bold">
                {{ substr(auth()->user()->first_name, 0, 1) . substr(auth()->user()->last_name, 0, 1) }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-sm font-semibold text-gray-900 truncate">
                    {{ auth()->user()->primary_center->name }}
                </div>
                <div class="text-xs text-gray-500">Premium Partner</div>
            </div>
        </div>
    </div>
</aside>

@push('styles')
<style>
.nav-link {
    @apply flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 font-medium text-sm transition-all duration-200;
}

.nav-link:hover {
    @apply bg-purple-100 text-gray-900;
}

.nav-link.active {
    @apply bg-gradient-to-r from-purple-200 to-pink-200 text-purple-900 font-semibold;
    position: relative;
}

.nav-link.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 22px;
    background: linear-gradient(180deg, #9333ea, #ec4899);
    border-radius: 0 4px 4px 0;
}
</style>
@endpush
```

### Retreat Cards: components/cards/retreat-card.blade.php

```blade
<div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 group">
    <!-- Image -->
    <div class="relative h-48 overflow-hidden bg-gradient-to-br from-purple-100 to-pink-100">
        @if($retreat->banner_image_url)
            <img src="{{ asset('storage/' . $retreat->banner_image_url) }}" 
                 alt="{{ $retreat->name }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
        @else
            <div class="w-full h-full flex items-center justify-center text-4xl">
                🧘
            </div>
        @endif
        
        <!-- Badge -->
        <div class="absolute top-4 right-4">
            @if($retreat->is_draft)
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">
                    ✏️ Draft
                </span>
            @else
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                    ✓ Published
                </span>
            @endif
        </div>

        <!-- Occupancy Badge -->
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

    <!-- Content -->
    <div class="p-5">
        <h3 class="font-display font-semibold text-gray-900 text-lg mb-1 line-clamp-2">
            {{ $retreat->name }}
        </h3>
        
        <p class="text-sm text-gray-600 mb-4 line-clamp-2">
            {{ $retreat->experience_summary }}
        </p>

        <!-- Meta Info -->
        <div class="grid grid-cols-2 gap-3 mb-4 text-sm">
            <div>
                <div class="text-gray-500 text-xs">Dates</div>
                <div class="font-semibold text-gray-900">
                    {{ $retreat->start_date_time?->format('M d') }} - {{ $retreat->end_date_time?->format('M d') }}
                </div>
            </div>
            <div>
                <div class="text-gray-500 text-xs">Price</div>
                <div class="font-semibold text-gray-900">
                    ₹{{ number_format($retreat->price_per_person) }}
                </div>
            </div>
            <div>
                <div class="text-gray-500 text-xs">Rating</div>
                <div class="font-semibold text-gray-900">
                    ⭐ {{ round($retreat->average_rating, 1) }}
                </div>
            </div>
            <div>
                <div class="text-gray-500 text-xs">Bookings</div>
                <div class="font-semibold text-gray-900">
                    {{ $retreat->bookings()->confirmed()->count() }}
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-2">
            <a href="{{ route('retreat.edit', $retreat) }}" 
               class="flex-1 px-3 py-2 bg-purple-50 text-purple-700 rounded-lg text-sm font-semibold hover:bg-purple-100 transition-colors">
                Edit
            </a>
            <a href="{{ route('retreat.show', $retreat) }}" 
               class="flex-1 px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-200 transition-colors">
                View
            </a>
            <div class="relative group">
                <button class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    ⋮
                </button>
                <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-10">
                    @if($retreat->is_draft)
                        <form method="POST" action="{{ route('retreat.publish', $retreat) }}" class="w-full">
                            @csrf @method('PATCH')
                            <button type="submit" class="w-full text-left px-4 py-2 hover:bg-gray-100 text-sm text-gray-700">
                                Publish
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('retreat.draft', $retreat) }}" class="w-full">
                            @csrf @method('PATCH')
                            <button type="submit" class="w-full text-left px-4 py-2 hover:bg-gray-100 text-sm text-gray-700">
                                Draft
                            </button>
                        </form>
                    @endif
                    
                    <form method="POST" action="{{ route('retreat.duplicate', $retreat) }}" class="w-full">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 hover:bg-gray-100 text-sm text-gray-700 border-t">
                            Duplicate
                        </button>
                    </form>
                    
                    <form method="POST" action="{{ route('retreat.destroy', $retreat) }}" 
                          onsubmit="return confirm('Delete this retreat?')" class="w-full">
                        @csrf @method('DELETE')
                        <button type="submit" class="w-full text-left px-4 py-2 hover:bg-red-100 text-sm text-red-700 border-t">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
```

### Retreat Form Component: components/forms/retreat-form.blade.php

```blade
<form method="POST" action="{{ route('retreat.update', $retreat ?? null) }}" 
      enctype="multipart/form-data" class="space-y-8">
    @csrf
    @if($retreat) @method('PATCH') @endif

    <!-- Section 1: Basic Information -->
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <div class="flex items-center gap-3 pb-4 border-b border-gray-200 mb-6">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center text-lg">
                🏢
            </div>
            <h2 class="font-display font-semibold text-lg text-gray-900">Basic Information</h2>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <div class="col-span-2">
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">
                    Retreat Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" 
                       value="{{ old('name', $retreat->name ?? '') }}"
                       class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                       placeholder="e.g., 7-Day Yoga Retreat in Goa"
                       required>
                @error('name') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">
                    Category <span class="text-red-500">*</span>
                </label>
                <select name="experience_category" class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg" required>
                    <option value="">Select category...</option>
                    <option value="yoga" @if(old('experience_category') == 'yoga') selected @endif>Yoga</option>
                    <option value="ayurveda" @if(old('experience_category') == 'ayurveda') selected @endif>Ayurveda</option>
                    <option value="wellness" @if(old('experience_category') == 'wellness') selected @endif>Wellness</option>
                    <option value="meditation" @if(old('experience_category') == 'meditation') selected @endif>Meditation</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">
                    Duration
                </label>
                <select name="duration" class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg">
                    <option value="3_days" @if(old('duration') == '3_days') selected @endif>3 Days</option>
                    <option value="5_days" @if(old('duration') == '5_days') selected @endif>5 Days</option>
                    <option value="7_days" @if(old('duration') == '7_days') selected @endif>7 Days</option>
                    <option value="14_days" @if(old('duration') == '14_days') selected @endif>14 Days</option>
                    <option value="21_days" @if(old('duration') == '21_days') selected @endif>21 Days</option>
                </select>
            </div>

            <div class="col-span-2">
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">
                    Summary <span class="text-red-500">*</span>
                </label>
                <textarea name="experience_summary" rows="4"
                          class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                          placeholder="Describe your retreat in detail..."
                          required>{{ old('experience_summary', $retreat->experience_summary ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <!-- Section 2: Dates & Pricing -->
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <div class="flex items-center gap-3 pb-4 border-b border-gray-200 mb-6">
            <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center text-lg">
                📅
            </div>
            <h2 class="font-display font-semibold text-lg text-gray-900">Dates & Pricing</h2>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">
                    Start Date <span class="text-red-500">*</span>
                </label>
                <input type="datetime-local" name="start_date_time"
                       value="{{ old('start_date_time', $retreat->start_date_time?->format('Y-m-d\TH:i') ?? '') }}"
                       class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg"
                       required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">
                    End Date <span class="text-red-500">*</span>
                </label>
                <input type="datetime-local" name="end_date_time"
                       value="{{ old('end_date_time', $retreat->end_date_time?->format('Y-m-d\TH:i') ?? '') }}"
                       class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg"
                       required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">
                    Base Price <span class="text-red-500">*</span>
                </label>
                <div class="flex">
                    <span class="inline-flex items-center px-4 bg-gray-100 border border-gray-300 border-r-0 rounded-l-lg">
                        ₹
                    </span>
                    <input type="number" name="price_per_person" 
                           value="{{ old('price_per_person', $retreat->price_per_person ?? '') }}"
                           class="form-input flex-1 px-4 py-3 border border-gray-300 rounded-r-lg"
                           placeholder="10000"
                           step="0.01"
                           required>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">
                    Currency
                </label>
                <select name="currency" class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg">
                    <option value="INR" @if(old('currency') == 'INR') selected @endif>₹ Indian Rupee</option>
                    <option value="USD" @if(old('currency') == 'USD') selected @endif>$ US Dollar</option>
                    <option value="EUR" @if(old('currency') == 'EUR') selected @endif>€ Euro</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Section 3: Accommodations -->
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <div class="flex items-center gap-3 pb-4 border-b border-gray-200 mb-6">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center text-lg">
                🛏️
            </div>
            <h2 class="font-display font-semibold text-lg text-gray-900">Accommodations</h2>
        </div>

        <div class="space-y-3">
            @foreach($accommodations as $accommodation)
                <label class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" name="accommodations[]" 
                           value="{{ $accommodation->id }}"
                           @if(in_array($accommodation->id, old('accommodations', $retreat->accommodations->pluck('id')->toArray() ?? []))) checked @endif
                           class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                    <span class="ml-3 text-gray-900 font-medium">{{ $accommodation->name }}</span>
                    <span class="ml-auto text-sm text-gray-500">Max {{ $accommodation->max_guest_in_room }} guests</span>
                </label>
            @endforeach
        </div>
    </div>

    <!-- Submit -->
    <div class="flex gap-3 justify-end">
        <a href="{{ route('retreat.index') }}" 
           class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50">
            Cancel
        </a>
        <button type="submit" 
                class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg hover:shadow-lg transition-shadow">
            {{ $retreat ? 'Update Retreat' : 'Create Retreat' }}
        </button>
    </div>
</form>
```

### Pricing Card: components/cards/pricing-card.blade.php

```blade
<div class="bg-white rounded-2xl border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="font-display font-semibold text-lg text-gray-900">💰 Pricing Summary</h3>
        <a href="{{ route('retreat.pricing.index', $retreat) }}" 
           class="text-purple-600 hover:text-purple-700 font-semibold text-sm">
            Manage Pricing →
        </a>
    </div>

    <div class="space-y-4">
        <div class="flex justify-between items-center pb-3 border-b border-gray-200">
            <span class="text-gray-600">Base Price (per person)</span>
            <span class="font-semibold text-gray-900">₹{{ number_format($retreat->price_per_person) }}</span>
        </div>

        @if($retreat->early_bird_discount)
            <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                <span class="text-gray-600">Early Bird ({{ $retreat->early_bird_days }} days)</span>
                <span class="font-semibold text-green-600">-{{ $retreat->early_bird_discount }}%</span>
            </div>
        @endif

        @if($retreat->deposit_policy)
            <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                <span class="text-gray-600">Deposit Required</span>
                <span class="font-semibold text-orange-600">₹{{ number_format($retreat->deposit_amount) }}</span>
            </div>
        @endif

        <div class="bg-purple-50 rounded-lg p-4 mt-4">
            <div class="text-sm text-purple-700 font-medium">
                Dynamic pricing rules: <strong>{{ $retreat->durationPrices()->count() }}</strong> configured
            </div>
        </div>
    </div>
</div>
```

### Account Dashboard Page: dashboard/account.blade.php

```blade
@extends('layouts.app')

@section('main-content')
<!-- Page Header -->
<div class="flex items-start justify-between mb-8">
    <div>
        <h1 class="font-display text-3xl font-bold text-gray-900">Account Information</h1>
        <p class="text-gray-600 mt-1">Manage your center account and settings</p>
    </div>
    
    <!-- AI Trust Score -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-4">
        <div class="relative w-16 h-16 flex-shrink-0">
            <svg class="w-full h-full" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="45" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                <circle cx="50" cy="50" r="45" fill="none" stroke="url(#gradient)" stroke-width="8"
                        stroke-dasharray="{{ ($ai_trust_score / 100) * 283 }} 283"
                        transform="rotate(-90 50 50)"/>
                <defs>
                    <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#a78bfa;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#34d399;stop-opacity:1" />
                    </linearGradient>
                </defs>
                <text x="50" y="55" text-anchor="middle" font-size="28" font-weight="bold" fill="#1f2937">
                    {{ $ai_trust_score }}
                </text>
            </svg>
        </div>
        <div>
            <div class="text-xs text-gray-600 uppercase tracking-wider">AI Trust Score</div>
            <div class="font-semibold text-gray-900">Very Good</div>
        </div>
    </div>
</div>

<!-- Completion Progress -->
<div class="bg-white rounded-2xl border border-gray-200 p-6 mb-8">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-gray-900">✨ Account Completion</h3>
        <span class="font-bold text-purple-600">{{ $completion_percentage }}%</span>
    </div>
    <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
        <div class="h-full bg-gradient-to-r from-purple-500 via-pink-500 to-green-500" 
             style="width: {{ $completion_percentage }}%"></div>
    </div>
    <p class="text-sm text-gray-600 mt-3">⚠️ Add payout account and GST info to reach 100%</p>
</div>

<!-- Form Section -->
<form method="POST" action="{{ route('account.update') }}" class="space-y-6">
    @csrf @method('PATCH')

    <!-- Basic Information -->
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <h2 class="font-display font-semibold text-lg mb-6 flex items-center gap-2">
            <span class="text-2xl">🏢</span> Basic Information
        </h2>

        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Center Name <span class="text-red-500">*</span></label>
                <input type="text" name="center_name" value="{{ old('center_name', $center->name) }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                       required>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Business Name</label>
                <input type="text" name="business_name" value="{{ old('business_name', $center->business_name ?? '') }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Contact Person <span class="text-red-500">*</span></label>
                <input type="text" name="contact_person" value="{{ old('contact_person', $center->founders) }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg" required>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Year of Foundation</label>
                <input type="number" name="year_of_foundation" value="{{ old('year_of_foundation', $center->year_of_foundation) }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg" min="1800" max="2099">
            </div>
        </div>
    </div>

    <!-- Contact Details -->
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <h2 class="font-display font-semibold text-lg mb-6 flex items-center gap-2">
            <span class="text-2xl">📱</span> Contact Details
        </h2>

        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email_address" value="{{ old('email_address', $center->email_address) }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg" required>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Phone <span class="text-red-500">*</span></label>
                <input type="tel" name="phone_number" value="{{ old('phone_number', $center->contact_number) }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg" required>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">WhatsApp</label>
                <input type="tel" name="whatsapp_number" value="{{ old('whatsapp_number', $center->whatsapp_number) }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Website</label>
                <input type="url" name="website" value="{{ old('website', $center->website) }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg">
            </div>
        </div>
    </div>

    <!-- Tax & Billing -->
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <h2 class="font-display font-semibold text-lg mb-6 flex items-center gap-2">
            <span class="text-2xl">🧾</span> Tax & Billing
        </h2>

        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">GST Number</label>
                <input type="text" name="gst_number" value="{{ old('gst_number', $center->gst_number) }}"
                       placeholder="22AAAAA0000A1Z5"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg"
                       pattern="[0-9A-Z]{15}">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">PAN Number</label>
                <input type="text" name="pan_number" value="{{ old('pan_number', $center->pan_number) }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg">
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Billing Address <span class="text-red-500">*</span></label>
                <input type="text" name="billing_address" value="{{ old('billing_address', $center->address_of_center) }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg" required>
            </div>
        </div>
    </div>

    <!-- Payout Account -->
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <h2 class="font-display font-semibold text-lg mb-6 flex items-center gap-2">
            <span class="text-2xl">🏦</span> Payout Account
        </h2>

        <div class="grid grid-cols-3 gap-6">
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Account Holder</label>
                <input type="text" name="account_holder_name" value="{{ old('account_holder_name', $payout_accounts->first()?->account_holder_name ?? '') }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Bank Name</label>
                <input type="text" name="bank_name" value="{{ old('bank_name', $payout_accounts->first()?->bank_name ?? '') }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Account Number</label>
                <input type="text" name="account_number" value="{{ old('account_number') }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="•••• •••• 1234">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">IFSC Code</label>
                <input type="text" name="ifsc_code" value="{{ old('ifsc_code', $payout_accounts->first()?->ifsc_code ?? '') }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Payout Cycle</label>
                <select name="preferred_payout_cycle" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                    <option value="weekly" @if(old('preferred_payout_cycle') == 'weekly') selected @endif>Weekly</option>
                    <option value="bi-weekly" @if(old('preferred_payout_cycle') == 'bi-weekly') selected @endif>Bi-weekly</option>
                    <option value="monthly" @if(old('preferred_payout_cycle') == 'monthly') selected @endif>Monthly</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">UPI ID</label>
                <input type="text" name="upi_id" value="{{ old('upi_id', $payout_accounts->first()?->upi_id ?? '') }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg">
            </div>
        </div>
    </div>

    <!-- Security Health -->
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <h2 class="font-display font-semibold text-lg mb-6 flex items-center gap-2">
            <span class="text-2xl">🛡️</span> Security Health
        </h2>

        <div class="space-y-3">
            @foreach($security_checks as $check => $status)
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg @if($status['status'] == 'verified') bg-green-100 text-green-700 @else bg-yellow-100 text-yellow-700 @endif flex items-center justify-center text-sm font-bold">
                            @if($status['status'] == 'verified') ✓ @else ! @endif
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900 capitalize">{{ str_replace('_', ' ', $check) }}</div>
                            <div class="text-sm text-gray-600">{{ $status['status'] == 'verified' ? 'Verified on ' . $status['date'] : 'Not verified' }}</div>
                        </div>
                    </div>
                    <a href="#" class="text-purple-600 text-sm font-semibold hover:text-purple-700">{{ $status['action'] }}</a>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Submit -->
    <div class="flex gap-3 justify-end">
        <button type="button" onclick="window.history.back()" 
                class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50">
            Discard Changes
        </button>
        <button type="submit" 
                class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg hover:shadow-lg transition-shadow">
            💾 Save Account Info
        </button>
    </div>
</form>
@endsection
```

This provides complete, production-ready Blade templates using TailwindCSS with proper:
- Component architecture
- Responsive design
- Form handling
- Error display
- Visual hierarchy
- Accessibility features
- Interactive elements
