<?php
/**
 * BalanceBoat - Complete Controller Implementations
 * Copy these files to app/Http/Controllers/ directory
 */

// ============================================================================
// RETREAT CONTROLLER - app/Http/Controllers/Retreat/RetreatController.php
// ============================================================================

namespace App\Http\Controllers\Retreat;

use App\Http\Controllers\Controller;
use App\Models\Experience;
use App\Models\Category;
use App\Models\Amenity;
use App\Http\Requests\StoreRetreatRequest;
use App\Http\Requests\UpdateRetreatRequest;
use App\Services\RetreatService;
use App\Services\PricingEngine;
use Illuminate\Http\Request;

class RetreatController extends Controller
{
    public function __construct(
        private RetreatService $retreatService,
        private PricingEngine $pricingEngine
    ) {
        $this->middleware('auth');
    }

    public function index()
    {
        $center = auth()->user()->primary_center ?? auth()->user()->centers()->first();

        if (!$center) {
            return redirect()->route('dashboard')->with('error', 'No center assigned');
        }

        $retreats = $center->experiences()
            ->with(['accommodations', 'bookings', 'reviews'])
            ->orderByDesc('created_at')
            ->paginate(12);

        $totalRevenue = $center->experiences()
            ->with('bookings')
            ->get()
            ->sum(fn($e) => $e->bookings->where('payment_status', 'completed')->sum('pay_amount'));

        $totalBookings = $center->experiences()
            ->with('bookings')
            ->get()
            ->sum(fn($e) => $e->bookings->where('order_status', 'confirmed')->count());

        return view('retreat.index', [
            'retreats' => $retreats,
            'center' => $center,
            'total_revenue' => $totalRevenue,
            'total_bookings' => $totalBookings,
        ]);
    }

    public function create()
    {
        $center = auth()->user()->primary_center ?? auth()->user()->centers()->first();

        return view('retreat.create', [
            'center' => $center,
            'accommodations' => $center->accommodations()->get(),
            'categories' => Category::all(),
            'teachers' => $center->teachers()->get(),
            'amenities' => Amenity::all(),
        ]);
    }

    public function store(StoreRetreatRequest $request)
    {
        $center = auth()->user()->primary_center ?? auth()->user()->centers()->first();

        $retreat = $this->retreatService->createRetreat($center, $request->validated());

        if ($request->has('categories')) {
            $retreat->categories()->sync($request->categories);
        }

        if ($request->has('teachers')) {
            $retreat->teachers()->sync($request->teachers);
        }

        if ($request->has('amenities')) {
            $retreat->amenities()->sync($request->amenities);
        }

        // Create initial pricing
        $retreat->durationPrices()->create([
            'duration' => intval(str_replace('_days', '', $request->duration ?? '7')),
            'price' => $request->price_per_person,
            'currency' => $request->currency ?? 'INR'
        ]);

        // Handle banner image
        if ($request->hasFile('banner_image')) {
            $path = $request->file('banner_image')->store('retreats', 'public');
            $retreat->update(['banner_image_url' => $path]);
        }

        return redirect()
            ->route('retreat.edit', $retreat)
            ->with('success', 'Retreat created successfully');
    }

    public function edit(Experience $retreat)
    {
        $this->authorize('view', $retreat);

        $center = $retreat->center;

        return view('retreat.edit', [
            'retreat' => $retreat->load([
                'accommodations',
                'schedules',
                'teachers',
                'categories',
                'amenities',
                'durationPrices'
            ]),
            'accommodations' => $center->accommodations,
            'categories' => Category::all(),
            'teachers' => $center->teachers,
            'amenities' => Amenity::all(),
        ]);
    }

    public function update(UpdateRetreatRequest $request, Experience $retreat)
    {
        $this->authorize('update', $retreat);

        $this->retreatService->updateRetreat($retreat, $request->validated());

        if ($request->hasFile('banner_image')) {
            $path = $request->file('banner_image')->store('retreats', 'public');
            $retreat->update(['banner_image_url' => $path]);
        }

        if ($request->has('accommodations')) {
            $retreat->accommodations()->sync($request->accommodations);
        }

        if ($request->has('teachers')) {
            $retreat->teachers()->sync($request->teachers);
        }

        if ($request->has('amenities')) {
            $retreat->amenities()->sync($request->amenities);
        }

        return back()->with('success', 'Retreat updated successfully');
    }

    public function show(Experience $retreat)
    {
        $this->authorize('view', $retreat);

        return view('retreat.show', [
            'retreat' => $retreat->load([
                'center',
                'accommodations',
                'schedules',
                'teachers',
                'bookings',
                'galleries',
                'reviews'
            ]),
            'bookings' => $retreat->bookings()->paginate(10),
            'reviews' => $retreat->reviews()->with('user')->paginate(5),
            'metrics' => $this->retreatService->getRetreatSummary($retreat),
        ]);
    }

    public function publish(Experience $retreat)
    {
        $this->authorize('update', $retreat);

        try {
            $this->retreatService->publishRetreat($retreat);
            return back()->with('success', 'Retreat is now live and bookable');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function draft(Experience $retreat)
    {
        $this->authorize('update', $retreat);

        $this->retreatService->draftRetreat($retreat);
        return back()->with('success', 'Retreat moved to draft');
    }

    public function duplicate(Experience $retreat)
    {
        $this->authorize('view', $retreat);

        $cloned = $this->retreatService->duplicateRetreat($retreat);

        return redirect()
            ->route('retreat.edit', $cloned)
            ->with('success', 'Retreat duplicated. Adjust dates and publish.');
    }

    public function destroy(Experience $retreat)
    {
        $this->authorize('delete', $retreat);

        $name = $retreat->name;
        $this->retreatService->deleteRetreat($retreat);

        return redirect()
            ->route('retreat.index')
            ->with('success', "Retreat '{$name}' deleted successfully");
    }
}

// ============================================================================
// RETREAT PRICING CONTROLLER - app/Http/Controllers/Retreat/RetreatPricingController.php
// ============================================================================

namespace App\Http\Controllers\Retreat;

use App\Http\Controllers\Controller;
use App\Models\Experience;
use App\Http\Requests\StorePricingRequest;
use App\Services\PricingEngine;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RetreatPricingController extends Controller
{
    public function __construct(private PricingEngine $pricingEngine)
    {
        $this->middleware('auth');
    }

    public function index(Experience $retreat)
    {
        $this->authorize('view', $retreat);

        return view('retreat.pricing', [
            'retreat' => $retreat,
            'duration_prices' => $retreat->durationPrices()->get(),
            'accommodation_prices' => $retreat->accommodations()
                ->with('prices')
                ->get(),
        ]);
    }

    public function store(StorePricingRequest $request, Experience $retreat)
    {
        $this->authorize('update', $retreat);

        $pricingType = $request->pricing_type;

        match($pricingType) {
            'duration' => $retreat->durationPrices()->updateOrCreate(
                ['duration' => $request->duration],
                [
                    'price' => $request->price,
                    'promo_price' => $request->promo_price,
                    'currency' => $request->currency,
                ]
            ),
            'occupancy' => $retreat->accommodations()
                ->find($request->accommodation_id)
                ->prices()
                ->create([
                    'experience_id' => $retreat->id,
                    'accomodation_id' => $request->accommodation_id,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'price_per_night_per_guest' => $request->price,
                    'currency' => $request->currency,
                ]),
            default => null,
        };

        return back()->with('success', 'Pricing updated successfully');
    }

    public function calculatePrice(Request $request, Experience $retreat)
    {
        $request->validate([
            'accommodation_id' => 'required|integer',
            'arrival_date' => 'required|date',
            'departure_date' => 'required|date',
            'guest_count' => 'required|integer|min:1',
            'coupon_code' => 'nullable|string',
        ]);

        $accommodation = $retreat->accommodations()
            ->find($request->accommodation_id);

        if (!$accommodation) {
            return response()->json(['error' => 'Accommodation not found'], 404);
        }

        $pricing = $this->pricingEngine->calculateBookingPrice(
            $retreat,
            $accommodation,
            Carbon::parse($request->arrival_date),
            Carbon::parse($request->departure_date),
            $request->guest_count,
            $request->coupon_code
        );

        return response()->json($pricing);
    }
}

// ============================================================================
// BOOKING CONTROLLER - app/Http/Controllers/Booking/BookingController.php
// ============================================================================

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Experience;
use App\Models\User;
use App\Http\Requests\StoreBookingRequest;
use App\Services\BookingService;
use App\Services\PricingEngine;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function __construct(
        private BookingService $bookingService,
        private PricingEngine $pricingEngine
    ) {
        $this->middleware('auth')->except(['preview']);
    }

    public function index()
    {
        $center = auth()->user()->primary_center ?? auth()->user()->centers()->first();

        $bookings = $center->experiences()
            ->with('bookings.user')
            ->get()
            ->map(fn($exp) => $exp->bookings)
            ->flatten()
            ->sortByDesc('created_at');

        $totalRevenue = Booking::whereIn('experience_id', $center->experiences->pluck('id'))
            ->where('payment_status', 'completed')
            ->sum('pay_amount');

        $confirmedCount = Booking::whereIn('experience_id', $center->experiences->pluck('id'))
            ->where('order_status', 'confirmed')
            ->count();

        return view('booking.index', [
            'bookings' => collect($bookings)->paginate(20),
            'total_revenue' => $totalRevenue,
            'confirmed_count' => $confirmedCount,
        ]);
    }

    public function show(Booking $booking)
    {
        $this->authorize('view', $booking);

        return view('booking.show', [
            'booking' => $booking->load([
                'experience',
                'accommodation',
                'user',
                'userInfo',
                'addressInfo',
                'transactionInfo'
            ]),
        ]);
    }

    public function preview(Experience $retreat, Request $request)
    {
        $request->validate([
            'accommodation_id' => 'required|integer',
            'arrival_date' => 'required|date',
            'departure_date' => 'required|date',
            'guest_count' => 'required|integer|min:1',
        ]);

        $accommodation = $retreat->accommodations()->find($request->accommodation_id);

        if (!$accommodation) {
            return abort(404);
        }

        $pricing = $this->pricingEngine->calculateBookingPrice(
            $retreat,
            $accommodation,
            Carbon::parse($request->arrival_date),
            Carbon::parse($request->departure_date),
            $request->guest_count,
            $request->coupon_code ?? null
        );

        return view('booking.preview', [
            'retreat' => $retreat,
            'accommodation' => $accommodation,
            'pricing' => $pricing,
            'arrival_date' => $request->arrival_date,
            'departure_date' => $request->departure_date,
            'guest_count' => $request->guest_count,
        ]);
    }

    public function store(StoreBookingRequest $request)
    {
        $experience = Experience::findOrFail($request->experience_id);
        $accommodation = $experience->accommodations()->findOrFail($request->accommodation_id);

        try {
            // Get or create user
            $user = User::firstOrCreate(
                ['email' => $request->email],
                [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'phone_number' => $request->phone,
                    'password' => bcrypt(uniqid()),
                ]
            );

            $booking = $this->bookingService->createBooking(
                $experience,
                $accommodation,
                $user,
                $request->validated()
            );

            return redirect()
                ->route('booking.payment', $booking)
                ->with('success', 'Booking created. Please complete payment.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function confirm(Booking $booking, Request $request)
    {
        $this->authorize('update', $booking);

        $request->validate([
            'transaction_id' => 'required|string',
        ]);

        $this->bookingService->confirmBooking($booking, $request->transaction_id);

        return back()->with('success', 'Booking confirmed successfully');
    }

    public function cancel(Booking $booking)
    {
        $this->authorize('update', $booking);

        try {
            $this->bookingService->cancelBooking($booking);
            return back()->with('success', 'Booking cancelled');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}

// ============================================================================
// DASHBOARD ACCOUNT CONTROLLER - app/Http/Controllers/Dashboard/AccountController.php
// ============================================================================

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAccountRequest;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function show()
    {
        $user = auth()->user();
        $center = $user->primary_center ?? $user->centers()->first();

        if (!$center) {
            return redirect()->route('dashboard')
                ->with('error', 'No center assigned to your account');
        }

        return view('dashboard.account', [
            'user' => $user,
            'center' => $center,
            'completion_percentage' => $center->completion_percentage,
            'ai_trust_score' => $this->calculateTrustScore($center),
            'security_checks' => $this->getSecurityStatus($user),
            'payout_accounts' => $center->payoutAccounts()->get(),
            'commission' => $center->commission,
        ]);
    }

    public function update(UpdateAccountRequest $request)
    {
        $center = auth()->user()->primary_center ?? auth()->user()->centers()->first();

        $center->update($request->safe([
            'name',
            'business_name',
            'contact_person',
            'year_of_foundation',
            'email_address',
            'contact_number',
            'whatsapp_number',
            'website',
            'facebook_url',
            'instagram_url',
            'address_of_center',
            'city',
            'country',
            'gst_number',
            'pan_number'
        ]));

        // Update or create payout account
        if ($request->has('account_number')) {
            $center->payoutAccounts()->updateOrCreate(
                ['center_id' => $center->id],
                [
                    'account_holder_name' => $request->account_holder_name,
                    'bank_name' => $request->bank_name,
                    'account_number' => $request->account_number,
                    'ifsc_code' => $request->ifsc_code,
                    'upi_id' => $request->upi_id,
                    'preferred_payout_cycle' => $request->preferred_payout_cycle,
                ]
            );
        }

        return back()->with('success', 'Account information updated successfully');
    }

    private function calculateTrustScore($center): int
    {
        $score = 0;

        // Profile completeness (max 40)
        $profileFields = ['name', 'about_center', 'banner_image_url', 'address_of_center'];
        $filled = collect($profileFields)->filter(fn($f) => !empty($center->{$f}))->count();
        $score += round(($filled / count($profileFields)) * 40);

        // Security (max 20)
        $user = $center->users()->first();
        if ($user && $user->email_verified_at) $score += 10;
        if ($center->gst_number && $center->pan_number) $score += 10;

        // Activity (max 20)
        if ($center->experiences()->count() > 0) $score += 10;
        if ($center->experiences()->with('bookings')->get()->sum(fn($e) => $e->bookings->count()) > 0) $score += 10;

        // Reputation (max 20)
        $avgRating = $center->average_rating;
        if ($avgRating >= 4.5) $score += 20;
        elseif ($avgRating >= 4.0) $score += 15;
        elseif ($avgRating >= 3.5) $score += 10;

        return min(100, $score);
    }

    private function getSecurityStatus($user)
    {
        return [
            'email_verified' => [
                'status' => $user->email_verified_at ? 'verified' : 'unverified',
                'date' => $user->email_verified_at?->format('M d, Y'),
                'action' => $user->email_verified_at ? 'Change' : 'Verify'
            ],
            'password_strength' => [
                'status' => 'strong',
                'last_changed' => now()->subDays(45)->format('M d, Y'),
                'action' => 'Update'
            ],
            'two_fa' => [
                'status' => 'disabled',
                'action' => 'Enable 2FA'
            ],
            'whatsapp' => [
                'status' => $user->primary_center?->whatsapp_number ? 'verified' : 'unverified',
                'action' => 'Verify'
            ]
        ];
    }
}

// ============================================================================
// DASHBOARD OVERVIEW CONTROLLER - app/Http/Controllers/Dashboard/OverviewController.php
// ============================================================================

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OverviewController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $center = $user->primary_center ?? $user->centers()->first();

        if (!$center) {
            return view('dashboard.no-center');
        }

        $experiences = $center->experiences()->with('bookings')->get();

        $stats = [
            'total_retreats' => $experiences->count(),
            'published_retreats' => $experiences->where('is_bookable', true)->count(),
            'draft_retreats' => $experiences->where('is_draft', true)->count(),
            'total_bookings' => $experiences->sum(fn($e) => $e->bookings->where('order_status', 'confirmed')->count()),
            'total_revenue' => $experiences->sum(fn($e) => $e->bookings->where('payment_status', 'completed')->sum('pay_amount')),
            'average_rating' => $center->average_rating,
            'total_reviews' => $center->reviews()->count(),
            'capacity_utilization' => $this->calculateCapacityUtilization($experiences),
        ];

        return view('dashboard.index', [
            'center' => $center,
            'stats' => $stats,
            'recent_bookings' => $center->experiences()
                ->with('bookings.user')
                ->get()
                ->map(fn($e) => $e->bookings)
                ->flatten()
                ->sortByDesc('created_at')
                ->take(5),
            'upcoming_retreats' => $experiences
                ->where('start_date_time', '>', now())
                ->sortBy('start_date_time')
                ->take(5),
        ]);
    }

    private function calculateCapacityUtilization($experiences): float
    {
        $totalCapacity = $experiences->sum('total_spaces');
        if ($totalCapacity === 0) return 0;

        $occupied = $experiences->sum('occupied_spaces');
        return round(($occupied / $totalCapacity) * 100, 2);
    }
}

?>
