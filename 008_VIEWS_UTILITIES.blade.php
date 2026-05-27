{{-- 
  BalanceBoat - Complete Blade Views and Components
  Copy these to resources/views/ directory
--}}

{{-- ================================================================
   RETREAT CREATE/EDIT VIEW - resources/views/retreat/create.blade.php
================================================================ --}}

@extends('layouts.app')

@section('main-content')
<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Create Retreat</h1>
        <p class="text-gray-600 mt-1">Set up a new wellness retreat program</p>
    </div>

    <form action="{{ route('retreat.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf

        {{-- Basic Information --}}
        <div class="bg-white rounded-2xl p-8 border border-gray-200">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Basic Information</h2>

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">
                        Retreat Name *
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition"
                           placeholder="e.g., 7-Day Yoga Retreat">
                    @error('name')
                        <span class="text-red-600 text-sm mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">
                        Category *
                    </label>
                    <select name="experience_category"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition">
                        <option value="">Select Category</option>
                        <option value="yoga">Yoga</option>
                        <option value="meditation">Meditation</option>
                        <option value="wellness">Wellness</option>
                        <option value="detox">Detox</option>
                    </select>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-900 mb-2">
                    Summary *
                </label>
                <textarea name="experience_summary" rows="4"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition"
                          placeholder="Describe your retreat...">{{ old('experience_summary') }}</textarea>
                @error('experience_summary')
                    <span class="text-red-600 text-sm mt-1">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-900 mb-2">
                    Banner Image
                </label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                    <input type="file" name="banner_image" accept="image/*"
                           class="w-full">
                    <p class="text-gray-500 text-sm mt-2">JPEG, PNG up to 2MB</p>
                </div>
            </div>
        </div>

        {{-- Dates & Pricing --}}
        <div class="bg-white rounded-2xl p-8 border border-gray-200">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Dates & Pricing</h2>

            <div class="grid grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">
                        Start Date *
                    </label>
                    <input type="datetime-local" name="start_date_time"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">
                        End Date *
                    </label>
                    <input type="datetime-local" name="end_date_time"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">
                        Duration *
                    </label>
                    <select name="duration"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                        <option value="3_days">3 Days</option>
                        <option value="5_days">5 Days</option>
                        <option value="7_days" selected>7 Days</option>
                        <option value="10_days">10 Days</option>
                        <option value="14_days">14 Days</option>
                        <option value="21_days">21 Days</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">
                        Price per Person (₹) *
                    </label>
                    <input type="number" name="price_per_person" step="0.01"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg"
                           placeholder="10000">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">
                        Currency *
                    </label>
                    <select name="currency"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                        <option value="INR" selected>INR (₹)</option>
                        <option value="USD">USD ($)</option>
                        <option value="EUR">EUR (€)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">
                        Early Bird Discount (%)
                    </label>
                    <input type="number" name="early_bird_discount" step="0.01" min="0" max="100"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                </div>
            </div>
        </div>

        {{-- Accommodations --}}
        <div class="bg-white rounded-2xl p-8 border border-gray-200">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Accommodations *</h2>

            <div class="space-y-3">
                @forelse($accommodations as $accommodation)
                    <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-purple-50 transition">
                        <input type="checkbox" name="accommodations[]" value="{{ $accommodation->id }}"
                               class="rounded">
                        <span class="ml-3 font-medium text-gray-900">
                            {{ $accommodation->name }}
                        </span>
                    </label>
                @empty
                    <p class="text-gray-600 text-sm">No accommodations available</p>
                @endforelse
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex gap-4">
            <button type="submit"
                    class="px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg hover:shadow-lg transition-shadow">
                Create Retreat
            </button>
            <a href="{{ route('retreat.index') }}"
               class="px-8 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection

{{-- ================================================================
   BOOKING SHOW VIEW - resources/views/booking/show.blade.php
================================================================ --}}

@extends('layouts.app')

@section('main-content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Booking #{{ $booking->id }}</h1>
            <p class="text-gray-600 mt-1">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-semibold"
                      :class="'{{ $booking->order_status }}' === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
                    {{ ucfirst($booking->order_status) }}
                </span>
            </p>
        </div>
        @if($booking->canBeCancelled())
            <form method="POST" action="{{ route('booking.cancel', $booking) }}" onsubmit="return confirm('Cancel this booking?')">
                @csrf @method('POST')
                <button type="submit"
                        class="px-6 py-2 bg-red-100 text-red-700 font-semibold rounded-lg hover:bg-red-200">
                    Cancel Booking
                </button>
            </form>
        @endif
    </div>

    <div class="grid grid-cols-3 gap-6 mb-8">
        {{-- Booking Details --}}
        <div class="col-span-2 bg-white rounded-2xl p-8 border border-gray-200">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Booking Details</h2>

            <dl class="space-y-6">
                <div class="flex justify-between">
                    <dt class="text-gray-600">Retreat</dt>
                    <dd class="font-semibold text-gray-900">{{ $booking->experience->name }}</dd>
                </div>

                <div class="flex justify-between">
                    <dt class="text-gray-600">Accommodation</dt>
                    <dd class="font-semibold text-gray-900">{{ $booking->accommodation->title }}</dd>
                </div>

                <div class="flex justify-between">
                    <dt class="text-gray-600">Arrival Date</dt>
                    <dd class="font-semibold text-gray-900">{{ $booking->arrival_date->format('M d, Y') }}</dd>
                </div>

                <div class="flex justify-between">
                    <dt class="text-gray-600">Departure Date</dt>
                    <dd class="font-semibold text-gray-900">{{ $booking->end_date_time->format('M d, Y') }}</dd>
                </div>

                <div class="flex justify-between">
                    <dt class="text-gray-600">Number of Guests</dt>
                    <dd class="font-semibold text-gray-900">{{ $booking->guest_count }}</dd>
                </div>

                <div class="border-t pt-6 flex justify-between">
                    <dt class="text-gray-600 font-semibold">Total Amount</dt>
                    <dd class="font-bold text-2xl text-purple-600">₹{{ number_format($booking->pay_amount) }}</dd>
                </div>
            </dl>
        </div>

        {{-- Guest Info --}}
        <div class="bg-white rounded-2xl p-8 border border-gray-200">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Guest Information</h2>

            <div class="space-y-4">
                <div>
                    <p class="text-gray-600 text-sm">Name</p>
                    <p class="font-semibold text-gray-900">
                        {{ $booking->userInfo->firstname }} {{ $booking->userInfo->lastname }}
                    </p>
                </div>

                <div>
                    <p class="text-gray-600 text-sm">Email</p>
                    <p class="font-semibold text-gray-900">{{ $booking->userInfo->email }}</p>
                </div>

                <div>
                    <p class="text-gray-600 text-sm">Phone</p>
                    <p class="font-semibold text-gray-900">{{ $booking->userInfo->phone }}</p>
                </div>

                @if($booking->addressInfo)
                    <div>
                        <p class="text-gray-600 text-sm">City</p>
                        <p class="font-semibold text-gray-900">{{ $booking->addressInfo->billing_city }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Payment & Transaction --}}
    @if($booking->transactionInfo)
        <div class="bg-white rounded-2xl p-8 border border-gray-200">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Payment Details</h2>

            <div class="grid grid-cols-4 gap-6">
                <div>
                    <p class="text-gray-600 text-sm">Transaction ID</p>
                    <p class="font-semibold text-gray-900">{{ $booking->transactionInfo->tracking_id ?? 'N/A' }}</p>
                </div>

                <div>
                    <p class="text-gray-600 text-sm">Status</p>
                    <p class="font-semibold text-gray-900">{{ ucfirst($booking->payment_status) }}</p>
                </div>

                <div>
                    <p class="text-gray-600 text-sm">Mode</p>
                    <p class="font-semibold text-gray-900">{{ $booking->transactionInfo->payment_mode ?? 'N/A' }}</p>
                </div>

                <div>
                    <p class="text-gray-600 text-sm">Date</p>
                    <p class="font-semibold text-gray-900">{{ $booking->transactionInfo->trans_date?->format('M d, Y') ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

{{-- ================================================================
   ACCOUNT VIEW - resources/views/dashboard/account.blade.php
================================================================ --}}

@extends('layouts.app')

@section('main-content')
<div class="max-w-6xl">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Account Settings</h1>
    </div>

    <div class="grid grid-cols-3 gap-6 mb-8">
        {{-- Trust Score Card --}}
        <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl p-6 border border-purple-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-900">AI Trust Score</h3>
                <span class="text-3xl font-bold text-purple-600">{{ $ai_trust_score }}%</span>
            </div>
            <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-green-500 to-blue-500"
                     style="width: {{ $ai_trust_score }}%"></div>
            </div>
            <p class="text-sm text-gray-600 mt-3">Complete your profile to increase trust score</p>
        </div>

        {{-- Completion Card --}}
        <div class="bg-gradient-to-br from-blue-50 to-green-50 rounded-2xl p-6 border border-blue-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-900">Profile Completion</h3>
                <span class="text-3xl font-bold text-blue-600">{{ $completion_percentage }}%</span>
            </div>
            <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-blue-500 to-green-500"
                     style="width: {{ $completion_percentage }}%"></div>
            </div>
        </div>

        {{-- Security Card --}}
        <div class="bg-gradient-to-br from-green-50 to-blue-50 rounded-2xl p-6 border border-green-200">
            <h3 class="font-bold text-gray-900 mb-4">Security Health</h3>
            <div class="space-y-2 text-sm">
                @foreach($security_checks as $check => $status)
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-2 h-2 rounded-full"
                              :class="'{{ $status['status'] }}' === 'verified' ? 'bg-green-500' : 'bg-gray-300'"></span>
                        <span class="text-gray-700">{{ ucfirst(str_replace('_', ' ', $check)) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Center Information Form --}}
    <form method="POST" action="{{ route('account.update') }}" class="space-y-8">
        @csrf @method('PATCH')

        {{-- Basic Info --}}
        <div class="bg-white rounded-2xl p-8 border border-gray-200">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Center Information</h2>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Center Name *</label>
                    <input type="text" name="center_name" value="{{ old('center_name', $center->name) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Business Name</label>
                    <input type="text" name="business_name" value="{{ old('business_name', $center->business_name) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Email *</label>
                    <input type="email" name="email_address" value="{{ old('email_address', $center->email_address) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Phone *</label>
                    <input type="tel" name="phone_number" value="{{ old('phone_number', $center->contact_number) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                </div>
            </div>
        </div>

        {{-- Tax & Bank Details --}}
        <div class="bg-white rounded-2xl p-8 border border-gray-200">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Tax & Bank Details</h2>

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">GST Number</label>
                    <input type="text" name="gst_number" value="{{ old('gst_number', $center->gst_number) }}"
                           placeholder="15-character GST number"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">PAN Number</label>
                    <input type="text" name="pan_number" value="{{ old('pan_number', $center->pan_number) }}"
                           placeholder="10-character PAN"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                </div>
            </div>

            <h3 class="text-lg font-bold text-gray-900 mb-4">Payout Account</h3>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Account Holder Name</label>
                    <input type="text" name="account_holder_name"
                           value="{{ old('account_holder_name', $payout_accounts->first()?->account_holder_name) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Bank Name</label>
                    <input type="text" name="bank_name"
                           value="{{ old('bank_name', $payout_accounts->first()?->bank_name) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Account Number</label>
                    <input type="text" name="account_number"
                           value="{{ old('account_number', $payout_accounts->first()?->account_number) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">IFSC Code</label>
                    <input type="text" name="ifsc_code"
                           value="{{ old('ifsc_code', $payout_accounts->first()?->ifsc_code) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">UPI ID</label>
                    <input type="text" name="upi_id"
                           value="{{ old('upi_id', $payout_accounts->first()?->upi_id) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Payout Frequency</label>
                    <select name="preferred_payout_cycle"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                        <option value="weekly">Weekly</option>
                        <option value="bi-weekly">Bi-weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex gap-4">
            <button type="submit"
                    class="px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-lg hover:shadow-lg transition-shadow">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection

<?php
/**
 * UTILITY CLASSES
 * Copy these to app/Utilities/
 */

// ============================================================================
// RESPONSE BUILDER - app/Utilities/ResponseBuilder.php
// ============================================================================

namespace App\Utilities;

class ResponseBuilder
{
    public static function success($data, $message = 'Operation successful', $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public static function error($message = 'Operation failed', $code = 400, $errors = [])
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    public static function paginated($items, $message = 'Data retrieved')
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $items->items(),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ]
        ]);
    }
}

// ============================================================================
// DATE RANGE HELPER - app/Utilities/DateRange.php
// ============================================================================

namespace App\Utilities;

use Carbon\Carbon;

class DateRange
{
    public static function isWithinRange(Carbon $date, Carbon $start, Carbon $end): bool
    {
        return $date->between($start, $end);
    }

    public static function getDaysBetween(Carbon $start, Carbon $end): int
    {
        return $end->diffInDays($start);
    }

    public static function getMonthsBetween(Carbon $start, Carbon $end): int
    {
        return $end->diffInMonths($start);
    }

    public static function isInPeakSeason(Carbon $date): bool
    {
        $month = $date->month;
        return in_array($month, [10, 11, 12, 1, 2, 3]);
    }

    public static function isInMonsooon(Carbon $date): bool
    {
        $month = $date->month;
        return in_array($month, [6, 7, 8, 9]);
    }
}

// ============================================================================
// FORMATTERS - app/Utilities/Formatters.php
// ============================================================================

namespace App\Utilities;

class Formatters
{
    public static function price($amount, $currency = 'INR'): string
    {
        return match($currency) {
            'INR' => '₹' . number_format($amount, 2),
            'USD' => '$' . number_format($amount, 2),
            'EUR' => '€' . number_format($amount, 2),
            'GBP' => '£' . number_format($amount, 2),
            default => number_format($amount, 2),
        };
    }

    public static function percentage($value, $total): string
    {
        if ($total === 0) return '0%';
        return round(($value / $total) * 100, 2) . '%';
    }

    public static function dateRange($startDate, $endDate): string
    {
        return $startDate->format('M d') . ' - ' . $endDate->format('M d, Y');
    }

    public static function shortName($firstName, $lastName): string
    {
        return substr($firstName, 0, 1) . substr($lastName, 0, 1);
    }
}

?>
