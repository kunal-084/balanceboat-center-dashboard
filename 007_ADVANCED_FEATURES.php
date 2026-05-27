<?php
/**
 * BalanceBoat - Additional Controllers, Policies, Events, Jobs, Tests
 * Copy these files to their respective directories
 */

// ============================================================================
// API PRICING CONTROLLER - app/Http/Controllers/Api/PricingController.php
// ============================================================================

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Experience;
use App\Models\ExperienceAccommodation;
use App\Services\PricingEngine;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PricingController extends Controller
{
    public function __construct(private PricingEngine $pricingEngine)
    {
        $this->middleware('auth:sanctum');
    }

    public function calculate(Request $request)
    {
        $request->validate([
            'experience_id' => 'required|integer|exists:experiences,id',
            'accommodation_id' => 'required|integer|exists:experience_accomodations,id',
            'arrival_date' => 'required|date|after_or_equal:today',
            'departure_date' => 'required|date|after:arrival_date',
            'guest_count' => 'required|integer|min:1|max:20',
            'coupon_code' => 'nullable|string|max:50',
        ]);

        $experience = Experience::findOrFail($request->experience_id);
        $accommodation = $experience->accommodations()
            ->findOrFail($request->accommodation_id);

        $pricing = $this->pricingEngine->calculateBookingPrice(
            $experience,
            $accommodation,
            Carbon::parse($request->arrival_date),
            Carbon::parse($request->departure_date),
            $request->guest_count,
            $request->coupon_code
        );

        return response()->json([
            'success' => true,
            'data' => $pricing
        ]);
    }
}

// ============================================================================
// API AVAILABILITY CONTROLLER - app/Http/Controllers/Api/AvailabilityController.php
// ============================================================================

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Experience;
use App\Models\ExperienceAccommodation;
use App\Services\AvailabilityEngine;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AvailabilityController extends Controller
{
    public function __construct(private AvailabilityEngine $availabilityEngine)
    {
        $this->middleware('auth:sanctum');
    }

    public function calendar(Request $request)
    {
        $request->validate([
            'experience_id' => 'required|integer|exists:experiences,id',
            'accommodation_id' => 'nullable|integer|exists:experience_accomodations,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $experience = Experience::findOrFail($request->experience_id);
        $accommodation = $request->accommodation_id ?
            $experience->accommodations()->findOrFail($request->accommodation_id) : null;

        $calendar = $this->availabilityEngine->getAvailabilityCalendar(
            $experience,
            $accommodation,
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        return response()->json([
            'success' => true,
            'data' => $calendar
        ]);
    }
}

// ============================================================================
// API RETREAT CONTROLLER - app/Http/Controllers/Api/RetreatController.php
// ============================================================================

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Experience;
use App\Services\RetreatService;
use Illuminate\Http\Request;

class RetreatController extends Controller
{
    public function __construct(private RetreatService $retreatService)
    {
        $this->middleware('auth:sanctum');
    }

    public function summary(Experience $retreat)
    {
        return response()->json([
            'success' => true,
            'data' => $this->retreatService->getRetreatSummary($retreat)
        ]);
    }

    public function availability(Experience $retreat, Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $accommodations = $retreat->accommodations()
            ->with(['bookings' => fn($q) => $q->where('order_status', 'confirmed')])
            ->get()
            ->map(fn($acc) => [
                'id' => $acc->id,
                'title' => $acc->title,
                'capacity' => $acc->max_guest_in_room,
                'price' => $acc->price_per_night_per_guest,
                'availability' => [
                    'total_spaces' => $acc->max_guest_in_room,
                    'booked_spaces' => $acc->bookings->sum('guest_count'),
                    'available_spaces' => $acc->max_guest_in_room - $acc->bookings->sum('guest_count'),
                ]
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'retreat_id' => $retreat->id,
                'retreat_name' => $retreat->name,
                'accommodations' => $accommodations
            ]
        ]);
    }
}

// ============================================================================
// POLICIES - app/Policies/
// ============================================================================

namespace App\Policies;

use App\Models\User;
use App\Models\Experience;

class ExperiencePolicy
{
    public function view(User $user, Experience $experience): bool
    {
        return $user->hasCenter($experience->center_id) ||
               $user->centers()->where('id', $experience->center_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->centers()->exists();
    }

    public function update(User $user, Experience $experience): bool
    {
        return $user->hasCenter($experience->center_id);
    }

    public function delete(User $user, Experience $experience): bool
    {
        return $user->hasCenter($experience->center_id);
    }

    public function publish(User $user, Experience $experience): bool
    {
        return $user->hasCenter($experience->center_id);
    }
}

namespace App\Policies;

use App\Models\User;
use App\Models\Booking;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        // Center admin can view bookings for their center
        if ($user->hasCenter($booking->experience->center_id)) {
            return true;
        }

        // Booking user can view their own booking
        return $booking->user_id === $user->id;
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->hasCenter($booking->experience->center_id);
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $user->hasCenter($booking->experience->center_id) ||
               $booking->user_id === $user->id;
    }
}

namespace App\Policies;

use App\Models\User;
use App\Models\Center;

class CenterPolicy
{
    public function view(User $user, Center $center): bool
    {
        return $user->hasCenter($center->id);
    }

    public function update(User $user, Center $center): bool
    {
        return $user->hasCenter($center->id) &&
               $user->centers()
                   ->wherePivot('role', 'admin')
                   ->where('id', $center->id)
                   ->exists();
    }

    public function manageTeam(User $user, Center $center): bool
    {
        return $this->update($user, $center);
    }
}

// ============================================================================
// EVENTS - app/Events/
// ============================================================================

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingConfirmed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Booking $booking)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('center.' . $this->booking->experience->center_id),
        ];
    }
}

namespace App\Events;

use App\Models\Experience;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RetreatPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public Experience $retreat)
    {
    }
}

namespace App\Events;

use App\Models\Experience;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RetreatDrafted
{
    use Dispatchable, SerializesModels;

    public function __construct(public Experience $retreat)
    {
    }
}

// ============================================================================
// JOBS - app/Jobs/
// ============================================================================

namespace App\Jobs;

use App\Models\Experience;
use App\Services\SchedulingEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateRecurringRetreatDates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Experience $experience,
        public int $limit = 50
    ) {}

    public function handle(SchedulingEngine $schedulingEngine)
    {
        // Clear existing recurring instances
        $this->experience->recurringRules()
            ->where('is_cancelled', false)
            ->delete();

        // Generate new dates
        $dates = $schedulingEngine->generateRecurringDates($this->experience, $this->limit);

        // Log or store the generated dates
        \Log::info("Generated {$this->limit} recurring dates for retreat {$this->experience->id}");
    }
}

namespace App\Jobs;

use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessBookingPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $transactionId,
        public float $amount
    ) {}

    public function handle(BookingService $bookingService)
    {
        try {
            // Verify payment with gateway
            $verified = $this->verifyPayment($this->transactionId, $this->amount);

            if ($verified) {
                $bookingService->confirmBooking($this->booking, $this->transactionId);
            }
        } catch (\Exception $e) {
            \Log::error("Payment processing failed for booking {$this->booking->id}: " . $e->getMessage());
            $this->fail($e);
        }
    }

    private function verifyPayment(string $transactionId, float $amount): bool
    {
        // Implement payment gateway verification
        // Example: Razorpay, Stripe, etc.
        return true; // Placeholder
    }
}

namespace App\Jobs;

use App\Models\Experience;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculatePricingForRetreats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        // Update pricing recommendations for all active retreats
        Experience::where('is_bookable', true)
            ->chunk(100, function ($retreats) {
                foreach ($retreats as $retreat) {
                    // Calculate recommendations based on:
                    // - Occupancy trends
                    // - Seasonal demand
                    // - Historical booking patterns
                    // - Competitor pricing
                }
            });

        \Log::info("Pricing calculations completed");
    }
}

// ============================================================================
// LISTENERS - app/Listeners/
// ============================================================================

namespace App\Listeners;

use App\Events\BookingConfirmed;
use App\Notifications\BookingConfirmedNotification;

class SendBookingConfirmationNotification
{
    public function handle(BookingConfirmed $event)
    {
        $booking = $event->booking;

        // Send confirmation email to guest
        $booking->user->notify(new BookingConfirmedNotification($booking));

        // Notify center admin
        foreach ($booking->experience->center->users as $admin) {
            $admin->notify(new \App\Notifications\NewBookingNotification($booking));
        }
    }
}

// ============================================================================
// NOTIFICATIONS - app/Notifications/
// ============================================================================

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->greeting("Booking Confirmed!")
            ->line("Your booking for {$this->booking->experience->name} is confirmed.")
            ->line("Arrival Date: {$this->booking->arrival_date->format('M d, Y')}")
            ->line("Total Amount: ₹" . number_format($this->booking->pay_amount))
            ->action('View Booking', route('booking.show', $this->booking))
            ->line('Thank you for choosing BalanceBoat!');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'experience_name' => $this->booking->experience->name,
            'amount' => $this->booking->pay_amount,
        ];
    }
}

// ============================================================================
// TEST CASES - tests/Feature/
// ============================================================================

namespace Tests\Feature;

use App\Models\Experience;
use App\Models\Center;
use App\Models\User;
use App\Services\PricingEngine;
use Carbon\Carbon;
use Tests\TestCase;

class PricingEngineTest extends TestCase
{
    private PricingEngine $pricingEngine;
    private Experience $retreat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricingEngine = app(PricingEngine::class);

        // Create test retreat
        $center = Center::factory()->create();
        $this->retreat = Experience::factory()
            ->for($center)
            ->create([
                'price_per_person' => 10000,
                'early_bird_discount' => 10,
                'early_bird_days' => 30,
            ]);

        // Add accommodation
        $this->retreat->accommodations()->create([
            'title' => 'Standard Room',
            'price_per_night_per_guest' => 2000,
            'max_guest_in_room' => 4,
        ]);
    }

    public function test_calculates_base_price()
    {
        $accommodation = $this->retreat->accommodations()->first();

        $pricing = $this->pricingEngine->calculateBookingPrice(
            $this->retreat,
            $accommodation,
            Carbon::now()->addDays(10),
            Carbon::now()->addDays(17),
            2
        );

        $this->assertArrayHasKey('base_price', $pricing);
        $this->assertArrayHasKey('final_amount', $pricing);
        $this->assertGreaterThan(0, $pricing['final_amount']);
    }

    public function test_applies_early_bird_discount()
    {
        $accommodation = $this->retreat->accommodations()->first();

        $pricing = $this->pricingEngine->calculateBookingPrice(
            $this->retreat,
            $accommodation,
            Carbon::now()->addDays(40), // Beyond early bird days
            Carbon::now()->addDays(47),
            2
        );

        $this->assertEquals(0, $pricing['early_bird_discount']);

        $pricing2 = $this->pricingEngine->calculateBookingPrice(
            $this->retreat,
            $accommodation,
            Carbon::now()->addDays(20), // Within early bird days
            Carbon::now()->addDays(27),
            2
        );

        $this->assertGreaterThan(0, $pricing2['early_bird_discount']);
    }

    public function test_applies_occupancy_discount()
    {
        $accommodation = $this->retreat->accommodations()->first();

        // Single guest - no discount
        $pricing1 = $this->pricingEngine->calculateBookingPrice(
            $this->retreat,
            $accommodation,
            Carbon::now()->addDays(10),
            Carbon::now()->addDays(17),
            1
        );

        // 4 guests - 5% discount
        $pricing4 = $this->pricingEngine->calculateBookingPrice(
            $this->retreat,
            $accommodation,
            Carbon::now()->addDays(10),
            Carbon::now()->addDays(17),
            4
        );

        $this->assertGreaterThan($pricing4['final_amount'], $pricing1['final_amount']);
    }
}

namespace Tests\Feature;

use App\Models\Experience;
use App\Models\Center;
use App\Models\User;
use App\Models\Booking;
use Tests\TestCase;

class BookingTest extends TestCase
{
    private Center $center;
    private Experience $retreat;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->center = Center::factory()->create();
        $this->retreat = Experience::factory()
            ->for($this->center)
            ->create(['is_bookable' => true]);

        $this->retreat->accommodations()->create([
            'title' => 'Standard',
            'price_per_night_per_guest' => 2000,
            'max_guest_in_room' => 4,
        ]);

        $this->user = User::factory()->create();
    }

    public function test_can_create_booking()
    {
        $response = $this->post('/booking', [
            'experience_id' => $this->retreat->id,
            'accommodation_id' => $this->retreat->accommodations()->first()->id,
            'arrival_date' => now()->addDays(10)->format('Y-m-d'),
            'departure_date' => now()->addDays(17)->format('Y-m-d'),
            'guest_count' => 2,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+919876543210',
            'agree_terms' => true,
        ]);

        $this->assertDatabaseHas('bookings', [
            'experience_id' => $this->retreat->id,
            'guest_count' => 2,
        ]);
    }

    public function test_cannot_book_unavailable_dates()
    {
        // Create first booking
        Booking::create([
            'experience_id' => $this->retreat->id,
            'experience_accomodation_id' => $this->retreat->accommodations()->first()->id,
            'user_id' => $this->user->id,
            'arrival_date' => now()->addDays(10),
            'guest_count' => 4, // Max capacity
            'price_per_person' => 2000,
            'booking_amount' => 16000,
            'pay_amount' => 16000,
            'order_status' => 'confirmed',
        ]);

        // Try to book same accommodation - should fail
        $response = $this->post('/booking', [
            'experience_id' => $this->retreat->id,
            'accommodation_id' => $this->retreat->accommodations()->first()->id,
            'arrival_date' => now()->addDays(10)->format('Y-m-d'),
            'departure_date' => now()->addDays(11)->format('Y-m-d'),
            'guest_count' => 2,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'phone' => '+919876543211',
            'agree_terms' => true,
        ]);

        $this->assertContains('availability', $response->baseResponse->getContent());
    }
}

namespace Tests\Unit;

use App\Services\SchedulingEngine;
use App\Models\Experience;
use App\Models\Center;
use Carbon\Carbon;
use Tests\TestCase;

class SchedulingEngineTest extends TestCase
{
    private SchedulingEngine $schedulingEngine;
    private Experience $retreat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schedulingEngine = app(SchedulingEngine::class);

        $center = Center::factory()->create();
        $this->retreat = Experience::factory()
            ->for($center)
            ->create([
                'duration' => '7_days',
                'is_recurring' => true,
            ]);

        // Create weekly recurring pattern
        $this->retreat->recurringRules()->create([
            'recurring_type' => 'Weekly',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addYear(),
            'separation_count' => 1,
            'day_of_week' => json_encode(['Monday']),
        ]);
    }

    public function test_generates_recurring_dates()
    {
        $dates = $this->schedulingEngine->generateRecurringDates($this->retreat, 10);

        $this->assertEquals(10, count($dates));
        $this->assertArrayHasKey('start_date', $dates[0]);
        $this->assertArrayHasKey('end_date', $dates[0]);
    }

    public function test_generates_itinerary()
    {
        $this->retreat->schedules()->createMany([
            ['schedule_day' => 'Day 1', 'activity_description' => 'Arrival & orientation'],
            ['schedule_day' => 'Day 2', 'activity_description' => 'Morning yoga'],
            ['schedule_day' => 'Day 3', 'activity_description' => 'Meditation'],
        ]);

        $itinerary = $this->schedulingEngine->getItinerary($this->retreat);

        $this->assertEquals(3, count($itinerary));
        $this->assertEquals('Day 1', $itinerary[0]['day']);
    }
}

?>
