<?php
/**
 * BalanceBoat - Complete Service Implementations
 * Copy these files to app/Services/ directory
 */

// ============================================================================
// PRICING ENGINE - app/Services/PricingEngine.php
// ============================================================================

namespace App\Services;

use App\Models\Experience;
use App\Models\ExperienceAccommodation;
use App\Models\Coupon;
use Carbon\Carbon;

class PricingEngine
{
    /**
     * Calculate total price for a booking with all discounts and taxes
     * Implements pricing hierarchy:
     * 1. Base price (by duration)
     * 2. Seasonal multiplier
     * 3. Occupancy-based discount
     * 4. Early-bird discount
     * 5. Coupon discount
     * 6. Tax calculation
     */
    public function calculateBookingPrice(
        Experience $experience,
        ExperienceAccommodation $accommodation,
        Carbon $arrivalDate,
        Carbon $departureDate,
        int $guestCount,
        ?string $couponCode = null
    ): array {
        $nights = $departureDate->diffInDays($arrivalDate);
        if ($nights <= 0) {
            $nights = 1;
        }

        // Get base price
        $basePrice = $this->getBasePriceForExperience($experience, $nights);

        // Get accommodation price
        $accommodationPrice = $this->getAccommodationPrice(
            $accommodation,
            $arrivalDate,
            $departureDate,
            $guestCount
        );

        // Get seasonal multiplier
        $seasonalMultiplier = $this->getSeasonalPricing($arrivalDate);

        // Calculate subtotal
        $subtotal = ($basePrice + $accommodationPrice) * $nights * $seasonalMultiplier;

        // Apply early bird discount
        $earlyBirdDiscount = $this->calculateEarlyBirdDiscount($experience, $arrivalDate, $subtotal);

        // Apply occupancy-based discount
        $occupancyDiscount = $this->calculateOccupancyDiscount($experience, $guestCount, $subtotal);

        // Apply coupon
        $couponDiscount = $couponCode ?
            $this->applyCoupon($couponCode, $subtotal, $experience) : 0;

        // Calculate totals
        $totalDiscount = $earlyBirdDiscount + $occupancyDiscount + $couponDiscount;
        $netAmount = $subtotal - $totalDiscount;
        $taxAmount = $this->calculateTax($experience, $netAmount);
        $finalAmount = $netAmount + $taxAmount;

        return [
            'base_price' => $basePrice,
            'accommodation_price' => $accommodationPrice,
            'nights' => $nights,
            'subtotal' => round($subtotal, 2),
            'seasonal_multiplier' => $seasonalMultiplier,
            'early_bird_discount' => round($earlyBirdDiscount, 2),
            'occupancy_discount' => round($occupancyDiscount, 2),
            'coupon_discount' => round($couponDiscount, 2),
            'total_discount' => round($totalDiscount, 2),
            'net_amount' => round($netAmount, 2),
            'tax_amount' => round($taxAmount, 2),
            'final_amount' => round($finalAmount, 2),
            'per_person_price' => round($finalAmount / max(1, $guestCount), 2),
            'currency' => $experience->currency ?? 'INR',
            'price_breakdown' => [
                'subtotal' => round($subtotal, 2),
                'discounts' => round($totalDiscount, 2),
                'tax' => round($taxAmount, 2),
            ]
        ];
    }

    private function getBasePriceForExperience(Experience $experience, int $nights): float
    {
        // Try to get from duration prices
        $durationPrice = $experience->durationPrices()
            ->where('duration', $nights)
            ->first();

        if ($durationPrice) {
            return $durationPrice->price;
        }

        // Fallback to base price
        return $experience->price_per_person ?? 0;
    }

    private function getAccommodationPrice(
        ExperienceAccommodation $accommodation,
        Carbon $arrivalDate,
        Carbon $departureDate,
        int $guestCount
    ): float {
        // Check for seasonal accommodation pricing
        $priceRecord = $accommodation->prices()
            ->where('start_date', '<=', $arrivalDate)
            ->where('end_date', '>=', $departureDate)
            ->first();

        if ($priceRecord) {
            return $priceRecord->price_per_night_per_guest;
        }

        return $accommodation->price_per_night_per_guest ?? 0;
    }

    private function getSeasonalPricing(Carbon $date): float
    {
        // Determine season based on month
        $month = $date->month;

        return match($month) {
            1, 2, 3 => 1.0,        // Regular season
            4, 5 => 0.8,           // Off-season
            6, 7, 8, 9 => 0.7,    // Monsoon (lower rates)
            10, 11, 12 => 1.2,    // Peak season
            default => 1.0
        };
    }

    private function calculateEarlyBirdDiscount(
        Experience $experience,
        Carbon $arrivalDate,
        float $baseAmount
    ): float {
        if (!$experience->early_bird_discount || !$experience->early_bird_days) {
            return 0;
        }

        $daysUntilStart = now()->diffInDays($arrivalDate, false);

        if ($daysUntilStart < $experience->early_bird_days) {
            $discountRate = $experience->early_bird_discount / 100;
            return $baseAmount * $discountRate;
        }

        return 0;
    }

    private function calculateOccupancyDiscount(
        Experience $experience,
        int $guestCount,
        float $baseAmount
    ): float {
        // Group discounts based on guest count
        $discountPercentage = match(true) {
            $guestCount >= 10 => 15,
            $guestCount >= 6 => 10,
            $guestCount >= 4 => 5,
            default => 0
        };

        return $baseAmount * ($discountPercentage / 100);
    }

    private function applyCoupon(string $code, float $amount, Experience $experience): float
    {
        // This would query the Coupon model in a real implementation
        // For now, return 0
        return 0;
    }

    private function calculateTax(Experience $experience, float $amount): float
    {
        $taxRate = $experience->center?->commission?->tax ?? 18;
        return $amount * ($taxRate / 100);
    }
}

// ============================================================================
// AVAILABILITY ENGINE - app/Services/AvailabilityEngine.php
// ============================================================================

namespace App\Services;

use App\Models\Experience;
use App\Models\ExperienceAccommodation;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AvailabilityEngine
{
    /**
     * Get availability calendar for retreat
     */
    public function getAvailabilityCalendar(
        Experience $experience,
        ?ExperienceAccommodation $accommodation = null,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $calendar = [];

        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            $calendar[$date->format('Y-m-d')] = [
                'date' => $date->format('Y-m-d'),
                'is_available' => $this->isDateAvailable($experience, $date),
                'availability_count' => $this->getAvailableCount($experience, $accommodation, $date),
                'total_capacity' => $this->getTotalCapacity($experience, $accommodation),
                'booked_count' => $this->getBookedCount($experience, $accommodation, $date),
                'is_blackout' => $this->isBlackoutDate($experience, $date),
            ];
        }

        return $calendar;
    }

    private function isDateAvailable(Experience $experience, Carbon $date): bool
    {
        // Check if date falls within experience date range
        if ($experience->start_date_time && $experience->end_date_time) {
            return $date->between(
                $experience->start_date_time->startOfDay(),
                $experience->end_date_time->endOfDay()
            );
        }

        // Check if recurring and falls within recurrence pattern
        if ($experience->is_recurring) {
            return $this->isDateInRecurringPattern($experience, $date);
        }

        return true; // Anytime retreat
    }

    private function getAvailableCount(
        Experience $experience,
        ?ExperienceAccommodation $accommodation,
        Carbon $date
    ): int {
        if ($accommodation) {
            $booked = $accommodation->bookings()
                ->where('arrival_date', $date->format('Y-m-d'))
                ->where('order_status', 'confirmed')
                ->sum('guest_count') ?? 0;

            return max(0, $accommodation->max_guest_in_room - $booked);
        }

        // Get total availability across all accommodations
        $booked = 0;
        $total = 0;

        foreach ($experience->accommodations as $acc) {
            $bookedForAcc = $acc->bookings()
                ->where('arrival_date', $date->format('Y-m-d'))
                ->where('order_status', 'confirmed')
                ->sum('guest_count') ?? 0;

            $booked += $bookedForAcc;
            $total += $acc->max_guest_in_room ?? 0;
        }

        return max(0, $total - $booked);
    }

    private function getTotalCapacity(
        Experience $experience,
        ?ExperienceAccommodation $accommodation
    ): int {
        if ($accommodation) {
            return $accommodation->max_guest_in_room ?? 0;
        }

        return $experience->accommodations->sum('max_guest_in_room') ?? 0;
    }

    private function getBookedCount(
        Experience $experience,
        ?ExperienceAccommodation $accommodation,
        Carbon $date
    ): int {
        if ($accommodation) {
            return $accommodation->bookings()
                ->where('arrival_date', $date->format('Y-m-d'))
                ->where('order_status', 'confirmed')
                ->sum('guest_count') ?? 0;
        }

        $booked = 0;
        foreach ($experience->accommodations as $acc) {
            $booked += $acc->bookings()
                ->where('arrival_date', $date->format('Y-m-d'))
                ->where('order_status', 'confirmed')
                ->sum('guest_count') ?? 0;
        }

        return $booked;
    }

    private function isBlackoutDate(Experience $experience, Carbon $date): bool
    {
        // Check recurring exceptions for cancellations
        return $experience->recurringRules()
            ->where('is_cancelled', true)
            ->whereDate('start_date', $date->format('Y-m-d'))
            ->exists();
    }

    private function isDateInRecurringPattern(Experience $experience, Carbon $date): bool
    {
        $recurring = $experience->recurringRules()->first();

        if (!$recurring) {
            return false;
        }

        // Check if date is after start and before end
        if ($date->isBefore($recurring->start_date) || $date->isAfter($recurring->end_date)) {
            return false;
        }

        // Check recurrence pattern
        return $this->matchesRecurrencePattern($recurring, $date);
    }

    private function matchesRecurrencePattern($recurring, Carbon $date): bool
    {
        return match($recurring->recurring_type) {
            'Daily' => true,
            'Weekly' => in_array(
                $date->format('l'),
                json_decode($recurring->day_of_week, true) ?? []
            ),
            'Monthly' => $date->day == $recurring->day_of_month,
            'Yearly' => $date->format('m-d') == Carbon::parse($recurring->month_of_year)->format('m-d'),
            default => false
        };
    }

    /**
     * Check if booking is possible
     */
    public function canBook(
        Experience $experience,
        ExperienceAccommodation $accommodation,
        Carbon $arrivalDate,
        Carbon $departureDate,
        int $guestCount
    ): bool {
        // Check each night
        $period = CarbonPeriod::create($arrivalDate, $departureDate);

        foreach ($period as $date) {
            $available = $this->getAvailableCount($experience, $accommodation, $date);

            if ($available < $guestCount) {
                return false;
            }
        }

        return true;
    }
}

// ============================================================================
// SCHEDULING ENGINE - app/Services/SchedulingEngine.php
// ============================================================================

namespace App\Services;

use App\Models\Experience;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class SchedulingEngine
{
    /**
     * Generate recurring dates based on recurrence rules
     */
    public function generateRecurringDates(
        Experience $experience,
        ?int $limit = 50
    ): array {
        $recurring = $experience->recurringRules()->first();

        if (!$recurring) {
            return [];
        }

        $dates = [];
        $current = Carbon::parse($recurring->start_date);
        $endDate = Carbon::parse($recurring->end_date);
        $count = 0;

        while ($current <= $endDate && $count < $limit) {
            if ($this->isValidRecurringDate($recurring, $current)) {
                $duration = $experience->duration_in_days ?? 7;

                $dates[] = [
                    'start_date' => $current->clone()->format('Y-m-d'),
                    'end_date' => $current->clone()->addDays($duration - 1)->format('Y-m-d'),
                    'is_booked' => false
                ];
                $count++;
            }

            $current = $this->getNextRecurringDate($recurring, $current);
        }

        return $dates;
    }

    private function isValidRecurringDate($recurring, Carbon $date): bool
    {
        $exceptions = $recurring->experience
            ->recurringRules()
            ->where('is_cancelled', true)
            ->pluck('start_date')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        if (in_array($date->format('Y-m-d'), $exceptions)) {
            return false;
        }

        return match($recurring->recurring_type) {
            'Daily' => true,
            'Weekly' => in_array(
                $date->format('l'),
                json_decode($recurring->day_of_week, true) ?? []
            ),
            'Monthly' => $date->day == $recurring->day_of_month,
            'Yearly' => $date->format('m-d') == Carbon::parse($recurring->month_of_year)->format('m-d'),
            default => false
        };
    }

    private function getNextRecurringDate($recurring, Carbon $current): Carbon
    {
        return match($recurring->recurring_type) {
            'Daily' => $current->addDays($recurring->separation_count ?? 1),
            'Weekly' => $current->addWeeks($recurring->separation_count ?? 1),
            'Monthly' => $current->addMonths($recurring->separation_count ?? 1),
            'Yearly' => $current->addYears($recurring->separation_count ?? 1),
            default => $current->addDays(1)
        };
    }

    /**
     * Get day-wise itinerary
     */
    public function getItinerary(Experience $experience): array
    {
        return $experience->schedules()
            ->orderBy('schedule_day')
            ->get()
            ->map(fn($schedule) => [
                'day' => $schedule->schedule_day,
                'start_time' => $schedule->schedule_start_time?->format('H:i'),
                'end_time' => $schedule->schedule_end_time?->format('H:i'),
                'activity' => $schedule->activity_description
            ])
            ->toArray();
    }

    /**
     * Clone retreat with new dates
     */
    public function cloneRetreat(Experience $original, Carbon $newStartDate): Experience
    {
        $duration = $original->duration_in_days ?? 7;

        $clone = $original->replicate(['id']);
        $clone->start_date_time = $newStartDate;
        $clone->end_date_time = $newStartDate->addDays($duration - 1);
        $clone->is_draft = true;
        $clone->save();

        // Clone accommodations
        foreach ($original->accommodations as $accommodation) {
            $clone->accommodations()->create([
                'accommodation_id' => $accommodation->accommodation_id,
                'title' => $accommodation->title,
                'about' => $accommodation->about,
                'price_per_night_per_guest' => $accommodation->price_per_night_per_guest,
                'currency' => $accommodation->currency,
                'max_guest_in_room' => $accommodation->max_guest_in_room,
                'accommodation_default' => $accommodation->accommodation_default,
            ]);
        }

        // Clone schedules
        foreach ($original->schedules as $schedule) {
            $clone->schedules()->create([
                'schedule_day' => $schedule->schedule_day,
                'schedule_start_time' => $schedule->schedule_start_time,
                'schedule_end_time' => $schedule->schedule_end_time,
                'activity_description' => $schedule->activity_description,
            ]);
        }

        // Clone pricing
        foreach ($original->durationPrices as $price) {
            $clone->durationPrices()->create([
                'duration' => $price->duration,
                'price' => $price->price,
                'promo_price' => $price->promo_price,
                'currency' => $price->currency,
            ]);
        }

        return $clone;
    }
}

// ============================================================================
// RETREAT SERVICE - app/Services/RetreatService.php
// ============================================================================

namespace App\Services;

use App\Models\Experience;
use App\Models\Center;
use App\Events\RetreatPublished;
use App\Events\RetreatDrafted;

class RetreatService
{
    public function __construct(
        private SchedulingEngine $schedulingEngine
    ) {}

    /**
     * Create new retreat
     */
    public function createRetreat(Center $center, array $data): Experience
    {
        $retreat = new Experience($data);
        $retreat->center_id = $center->id;
        $retreat->is_draft = true;
        $retreat->save();

        return $retreat;
    }

    /**
     * Update retreat
     */
    public function updateRetreat(Experience $retreat, array $data): Experience
    {
        $retreat->update($data);
        return $retreat;
    }

    /**
     * Publish retreat (make it bookable)
     */
    public function publishRetreat(Experience $retreat): bool
    {
        if (!$this->isRetreatComplete($retreat)) {
            throw new \Exception('Retreat must have all required fields before publishing');
        }

        $retreat->update(['is_draft' => false, 'is_bookable' => true]);
        event(new RetreatPublished($retreat));

        return true;
    }

    /**
     * Draft retreat (make it non-bookable)
     */
    public function draftRetreat(Experience $retreat): bool
    {
        $retreat->update(['is_draft' => true, 'is_bookable' => false]);
        event(new RetreatDrafted($retreat));

        return true;
    }

    /**
     * Delete retreat
     */
    public function deleteRetreat(Experience $retreat): bool
    {
        // Cancel all pending bookings
        $retreat->bookings()
            ->where('order_status', '!=', 'confirmed')
            ->update(['order_status' => 'cancelled']);

        return $retreat->delete();
    }

    /**
     * Duplicate retreat
     */
    public function duplicateRetreat(Experience $retreat, ?Carbon $newStartDate = null): Experience
    {
        return $this->schedulingEngine->cloneRetreat(
            $retreat,
            $newStartDate ?? $retreat->start_date_time->addMonths(1)
        );
    }

    /**
     * Check retreat completeness
     */
    private function isRetreatComplete(Experience $retreat): bool
    {
        return $retreat->name &&
               $retreat->center_id &&
               $retreat->accommodations()->exists() &&
               $retreat->price_per_person &&
               $retreat->start_date_time &&
               $retreat->end_date_time;
    }

    /**
     * Get retreat summary with metrics
     */
    public function getRetreatSummary(Experience $retreat): array
    {
        return [
            'id' => $retreat->id,
            'name' => $retreat->name,
            'status' => $retreat->is_draft ? 'draft' : 'published',
            'dates' => [
                'start' => $retreat->start_date_time?->format('Y-m-d'),
                'end' => $retreat->end_date_time?->format('Y-m-d'),
            ],
            'capacity' => $retreat->total_spaces,
            'booked' => $retreat->occupied_spaces,
            'available' => $retreat->available_spaces,
            'occupancy_percent' => $retreat->occupancy_percentage,
            'base_price' => $retreat->price_per_person,
            'bookings' => $retreat->bookings()->where('order_status', 'confirmed')->count(),
            'revenue' => $retreat->bookings()->where('payment_status', 'completed')->sum('pay_amount'),
            'rating' => $retreat->average_rating,
        ];
    }
}

// ============================================================================
// BOOKING SERVICE - app/Services/BookingService.php
// ============================================================================

namespace App\Services;

use App\Models\Experience;
use App\Models\ExperienceAccommodation;
use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use App\Events\BookingConfirmed;

class BookingService
{
    public function __construct(
        private PricingEngine $pricingEngine,
        private AvailabilityEngine $availabilityEngine
    ) {}

    /**
     * Create booking
     */
    public function createBooking(
        Experience $experience,
        ExperienceAccommodation $accommodation,
        User $user,
        array $bookingData
    ): Booking {
        $arrivalDate = Carbon::parse($bookingData['arrival_date']);
        $departureDate = Carbon::parse($bookingData['departure_date']);
        $guestCount = $bookingData['guest_count'];

        // Check availability
        if (!$this->availabilityEngine->canBook(
            $experience,
            $accommodation,
            $arrivalDate,
            $departureDate,
            $guestCount
        )) {
            throw new \Exception('Not enough availability for selected dates');
        }

        // Calculate pricing
        $pricing = $this->pricingEngine->calculateBookingPrice(
            $experience,
            $accommodation,
            $arrivalDate,
            $departureDate,
            $guestCount,
            $bookingData['coupon_code'] ?? null
        );

        // Create booking
        $booking = Booking::create([
            'experience_id' => $experience->id,
            'experience_accomodation_id' => $accommodation->id,
            'user_id' => $user->id,
            'arrival_date' => $arrivalDate,
            'start_date_time' => $arrivalDate->startOfDay(),
            'end_date_time' => $departureDate->endOfDay(),
            'duration' => $guestCount,
            'guest_count' => $guestCount,
            'price_per_person' => $pricing['per_person_price'],
            'booking_amount' => $pricing['subtotal'],
            'discount_amount' => $pricing['total_discount'],
            'pay_amount' => $pricing['final_amount'],
            'currency' => $experience->currency ?? 'INR',
            'order_status' => Booking::STATUS_PENDING,
            'payment_status' => Booking::PAYMENT_PENDING,
            'is_full_day_event' => $experience->is_full_day_event,
        ]);

        // Store user info
        $booking->userInfo()->create([
            'firstname' => $bookingData['first_name'],
            'lastname' => $bookingData['last_name'],
            'email' => $bookingData['email'],
            'phone' => $bookingData['phone'],
            'message' => $bookingData['message'] ?? null
        ]);

        // Store address info if provided
        if (isset($bookingData['billing_address'])) {
            $booking->addressInfo()->create([
                'billing_name' => $bookingData['billing_name'] ?? null,
                'billing_address' => $bookingData['billing_address'],
                'billing_city' => $bookingData['billing_city'] ?? null,
                'billing_state' => $bookingData['billing_state'] ?? null,
                'billing_zip' => $bookingData['billing_zip'] ?? null,
                'billing_country' => $bookingData['billing_country'] ?? null,
                'billing_tel' => $bookingData['billing_tel'] ?? null,
                'billing_email' => $bookingData['billing_email'] ?? null,
            ]);
        }

        return $booking;
    }

    /**
     * Confirm booking (after payment)
     */
    public function confirmBooking(Booking $booking, string $transactionId): bool
    {
        $booking->update([
            'order_status' => Booking::STATUS_CONFIRMED,
            'payment_status' => Booking::PAYMENT_COMPLETED,
            'transaction_id' => $transactionId
        ]);

        event(new BookingConfirmed($booking));

        return true;
    }

    /**
     * Cancel booking
     */
    public function cancelBooking(Booking $booking, ?string $reason = null): bool
    {
        if (!$booking->canBeCancelled()) {
            throw new \Exception('Booking cannot be cancelled');
        }

        $booking->update([
            'order_status' => Booking::STATUS_CANCELLED
        ]);

        // Process refund if needed
        if ($booking->payment_status === Booking::PAYMENT_COMPLETED) {
            $refundAmount = $this->calculateRefund($booking);
            // Process refund to payment gateway
        }

        return true;
    }

    private function calculateRefund(Booking $booking): float
    {
        $experience = $booking->experience;
        $cancelledDays = now()->diffInDays($booking->arrival_date);

        $policy = $experience->center?->commission;

        if (!$policy) {
            return $booking->pay_amount * 0.5; // Default 50%
        }

        if (!$policy->cancellation_policy_condition) {
            return $booking->pay_amount; // Full refund
        }

        if ($cancelledDays > $policy->cancellation_policy_days) {
            return $booking->pay_amount; // Full refund
        }

        return 0; // No refund
    }
}

?>
