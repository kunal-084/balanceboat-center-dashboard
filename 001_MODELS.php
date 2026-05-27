<?php
/**
 * BalanceBoat - Complete Model Implementations
 * Copy these files to app/Models/ directory
 */

// ============================================================================
// USER MODEL - app/Models/User.php
// ============================================================================

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasRoles;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone_number',
        'date_of_birth',
        'street_address',
        'city',
        'zipcode',
        'country',
        'profile_image_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'date_of_birth' => 'date',
    ];

    // Relations
    public function centers()
    {
        return $this->belongsToMany(Center::class)
            ->withPivot('role', 'status')
            ->withTimestamps();
    }

    public function primary_center()
    {
        return $this->belongsTo(Center::class, 'primary_center_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // Attributes
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getPrimaryCenter()
    {
        return $this->centers()->wherePivot('role', 'admin')->first();
    }

    public function hasCenter($centerId)
    {
        return $this->centers()->where('center_id', $centerId)->exists();
    }
}

// ============================================================================
// CENTER MODEL - app/Models/Center.php
// ============================================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Center extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'location',
        'center_type',
        'year_of_foundation',
        'founders',
        'about_center',
        'what_sets_us_apart',
        'our_philosophy',
        'our_mission',
        'center_highlights',
        'video_url',
        'banner_image_url',
        'website',
        'address_of_center',
        'city',
        'country',
        'email_address',
        'contact_number',
        'whatsapp_number',
        'facebook_url',
        'instagram_url',
        'have_accommodation',
        'is_draft',
        'user_id',
        'gst_number',
        'pan_number',
        'business_name',
    ];

    protected $casts = [
        'is_draft' => 'boolean',
    ];

    // Relations
    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role', 'status')
            ->withTimestamps();
    }

    public function experiences()
    {
        return $this->hasMany(Experience::class);
    }

    public function accommodations()
    {
        return $this->belongsToMany(Accommodation::class, 'center_accommodations');
    }

    public function commission()
    {
        return $this->hasOne(CenterCommission::class);
    }

    public function galleries()
    {
        return $this->hasMany(CenterImage::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function payoutAccounts()
    {
        return $this->hasMany(PayoutAccount::class);
    }

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'center_teachers');
    }

    public function centerUsers()
    {
        return $this->belongsToMany(User::class)->withPivot('role', 'status');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_draft', false);
    }

    public function scopeDraft($query)
    {
        return $query->where('is_draft', true);
    }

    // Attributes
    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    public function getCompletionPercentageAttribute()
    {
        $fields = [
            'name',
            'about_center',
            'address_of_center',
            'email_address',
            'contact_number',
            'banner_image_url',
        ];

        $filled = collect($fields)
            ->filter(fn($field) => !empty($this->{$field}))
            ->count();

        return round(($filled / count($fields)) * 100);
    }

    public function getTotalRevenueAttribute()
    {
        return $this->experiences()
            ->with('bookings')
            ->get()
            ->sum(fn($e) => $e->bookings->where('payment_status', 'completed')->sum('pay_amount'));
    }

    public function getTotalBookingsAttribute()
    {
        return $this->experiences()
            ->with('bookings')
            ->get()
            ->sum(fn($e) => $e->bookings->where('order_status', 'confirmed')->count());
    }
}

// ============================================================================
// EXPERIENCE MODEL - app/Models/Experience.php (RETREAT)
// ============================================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Experience extends Model
{
    use SoftDeletes;

    protected $table = 'experiences';

    protected $fillable = [
        'name',
        'slug',
        'center_id',
        'experience_category',
        'price_per_person',
        'currency',
        'batch_size',
        'experience_summary',
        'start_date_time',
        'end_date_time',
        'is_full_day_event',
        'is_recurring',
        'is_bookable',
        'is_draft',
        'video_url',
        'banner_image_url',
        'what_is_included',
        'what_is_not_included',
        'experience_highlights',
        'cancellation_policy',
        'deposit_policy',
        'deposit_amount',
        'early_bird_discount',
        'early_bird_days',
        'duration',
    ];

    protected $casts = [
        'is_full_day_event' => 'boolean',
        'is_recurring' => 'boolean',
        'is_bookable' => 'boolean',
        'is_draft' => 'boolean',
        'deposit_policy' => 'boolean',
        'start_date_time' => 'datetime',
        'end_date_time' => 'datetime',
    ];

    // Relations
    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'experience_category');
    }

    public function accommodations()
    {
        return $this->hasMany(ExperienceAccommodation::class);
    }

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'experience_teachers');
    }

    public function schedules()
    {
        return $this->hasMany(ExperienceSchedule::class)->orderBy('schedule_day', 'asc');
    }

    public function recurringRules()
    {
        return $this->hasMany(ExperienceRecurring::class);
    }

    public function durationPrices()
    {
        return $this->hasMany(ExperienceDurationPrice::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function galleries()
    {
        return $this->hasMany(ExperienceImage::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'experience_amenities');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_draft', false)->where('is_bookable', true);
    }

    public function scopeDraft($query)
    {
        return $query->where('is_draft', true);
    }

    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    // Attributes
    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    public function getDurationInDaysAttribute()
    {
        if ($this->start_date_time && $this->end_date_time) {
            return $this->end_date_time->diffInDays($this->start_date_time) + 1;
        }
        return (int)str_replace('_days', '', $this->duration ?? 7);
    }

    public function getTotalSpacesAttribute()
    {
        return $this->accommodations->sum('max_guest_in_room');
    }

    public function getOccupiedSpacesAttribute()
    {
        return $this->bookings()
            ->where('order_status', 'confirmed')
            ->sum('guest_count') ?? 0;
    }

    public function getAvailableSpacesAttribute()
    {
        return max(0, $this->total_spaces - $this->occupied_spaces);
    }

    public function getOccupancyPercentageAttribute()
    {
        if ($this->total_spaces === 0) return 0;
        return round(($this->occupied_spaces / $this->total_spaces) * 100, 2);
    }

    public function getTotalRevenueAttribute()
    {
        return $this->bookings()
            ->where('payment_status', 'completed')
            ->sum('pay_amount') ?? 0;
    }
}

// ============================================================================
// EXPERIENCE ACCOMMODATION MODEL - app/Models/ExperienceAccommodation.php
// ============================================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExperienceAccommodation extends Model
{
    protected $table = 'experience_accomodations';

    protected $fillable = [
        'experience_id',
        'accommodation_id',
        'title',
        'about',
        'price_per_night_per_guest',
        'currency',
        'max_guest_in_room',
        'accommodation_default',
    ];

    protected $casts = [
        'accommodation_default' => 'boolean',
    ];

    // Relations
    public function experience()
    {
        return $this->belongsTo(Experience::class);
    }

    public function accommodation()
    {
        return $this->belongsTo(Accommodation::class);
    }

    public function prices()
    {
        return $this->hasMany(ExperienceAccommodationPrice::class, 'accomodation_id');
    }

    public function galleries()
    {
        return $this->hasMany(ExperienceAccommodationImage::class, 'accomodation_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'experience_accomodation_id');
    }

    // Scopes
    public function scopeDefault($query)
    {
        return $query->where('accommodation_default', true);
    }

    // Business Logic
    public function getAvailableSpacesForDate($date)
    {
        $booked = $this->bookings()
            ->where('arrival_date', $date)
            ->where('order_status', 'confirmed')
            ->sum('guest_count') ?? 0;

        return max(0, $this->max_guest_in_room - $booked);
    }

    public function getPriceForDateAndOccupancy($date, $occupancy = 1)
    {
        $priceRule = $this->prices()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();

        return $priceRule?->price_per_night_per_guest ?? $this->price_per_night_per_guest ?? 0;
    }
}

// ============================================================================
// BOOKING MODEL - app/Models/Booking.php
// ============================================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'experience_id',
        'experience_accomodation_id',
        'user_id',
        'arrival_date',
        'start_date_time',
        'end_date_time',
        'duration',
        'guest_count',
        'price_per_person',
        'booking_amount',
        'discount_amount',
        'commission_amount',
        'pay_amount',
        'currency',
        'order_status',
        'payment_status',
        'transaction_id',
        'is_full_day_event',
        'is_recurring',
    ];

    protected $casts = [
        'arrival_date' => 'date',
        'start_date_time' => 'datetime',
        'end_date_time' => 'datetime',
        'is_full_day_event' => 'boolean',
        'is_recurring' => 'boolean',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';
    const PAYMENT_PENDING = 'pending';
    const PAYMENT_COMPLETED = 'completed';

    // Relations
    public function experience()
    {
        return $this->belongsTo(Experience::class);
    }

    public function accommodation()
    {
        return $this->belongsTo(ExperienceAccommodation::class, 'experience_accomodation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactionInfo()
    {
        return $this->hasOne(BookingTransactionInfo::class);
    }

    public function addressInfo()
    {
        return $this->hasOne(BookingTransactionAddressInfo::class);
    }

    public function userInfo()
    {
        return $this->hasOne(BookingUserInfo::class);
    }

    // Scopes
    public function scopeConfirmed($query)
    {
        return $query->where('order_status', self::STATUS_CONFIRMED);
    }

    public function scopePending($query)
    {
        return $query->where('order_status', self::STATUS_PENDING);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('arrival_date', [$startDate, $endDate]);
    }

    // Business Logic
    public function getNetAmountAttribute()
    {
        return $this->booking_amount - $this->discount_amount;
    }

    public function canBeCancelled()
    {
        return $this->order_status !== self::STATUS_CANCELLED &&
               $this->arrival_date->isFuture();
    }

    public function getTotalNightsAttribute()
    {
        if ($this->start_date_time && $this->end_date_time) {
            return $this->end_date_time->diffInDays($this->start_date_time);
        }
        return (int)$this->duration;
    }
}

// ============================================================================
// ADDITIONAL MODELS - Brief Implementation
// ============================================================================

// ExperienceSchedule - Day-wise itinerary
namespace App\Models;

class ExperienceSchedule extends Model
{
    protected $fillable = [
        'experience_id',
        'schedule_day',
        'schedule_start_time',
        'schedule_end_time',
        'activity_description',
    ];

    protected $casts = [
        'schedule_start_time' => 'datetime',
        'schedule_end_time' => 'datetime',
    ];

    public function experience()
    {
        return $this->belongsTo(Experience::class);
    }
}

// ExperienceRecurring - Recurring pattern
namespace App\Models;

class ExperienceRecurring extends Model
{
    protected $fillable = [
        'experience_id',
        'recurring_type',
        'start_date',
        'end_date',
        'separation_count',
        'max_num_of_occurrances',
        'day_of_week',
        'week_of_month',
        'day_of_month',
        'month_of_year',
    ];

    const TYPE_DAILY = 'Daily';
    const TYPE_WEEKLY = 'Weekly';
    const TYPE_MONTHLY = 'Monthly';
    const TYPE_YEARLY = 'Yearly';

    public function experience()
    {
        return $this->belongsTo(Experience::class);
    }
}

// Accommodation - Base room type
namespace App\Models;

class Accommodation extends Model
{
    protected $table = 'accomodation';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'max_guest_in_room',
        'banner_image_url',
    ];

    public function galleries()
    {
        return $this->hasMany(AccommodationImageGallery::class);
    }

    public function centers()
    {
        return $this->belongsToMany(Center::class, 'center_accommodations');
    }

    public function experienceAccommodations()
    {
        return $this->hasMany(ExperienceAccommodation::class);
    }
}

// ExperienceDurationPrice - Base pricing
namespace App\Models;

class ExperienceDurationPrice extends Model
{
    protected $fillable = [
        'experience_id',
        'duration',
        'price',
        'promo_price',
        'currency',
    ];

    public function experience()
    {
        return $this->belongsTo(Experience::class);
    }
}

// ExperienceAccommodationPrice - Occupancy pricing
namespace App\Models;

class ExperienceAccommodationPrice extends Model
{
    protected $table = 'experience_accomodation_prices';

    protected $fillable = [
        'experience_id',
        'accomodation_id',
        'duration',
        'start_date',
        'end_date',
        'avg_price',
        'price_per_night_per_guest',
        'promotional_price',
        'promotional_discount',
        'currency',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function experience()
    {
        return $this->belongsTo(Experience::class);
    }

    public function accommodation()
    {
        return $this->belongsTo(ExperienceAccommodation::class, 'accomodation_id');
    }
}

// Review
namespace App\Models;

class Review extends Model
{
    protected $fillable = [
        'experience_id',
        'center_id',
        'user_id',
        'booking_id',
        'rating',
        'title',
        'content',
        'source',
        'verified',
    ];

    public function experience()
    {
        return $this->belongsTo(Experience::class);
    }

    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

// Category
namespace App\Models;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'image_url', 'type'];

    public function experiences()
    {
        return $this->belongsToMany(Experience::class, 'experience_category');
    }
}

// Teacher
namespace App\Models;

class Teacher extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'meta_title',
        'short_description',
        'complete_bio',
        'teaching_since',
        'profile_image_url',
        'expertise_id',
    ];

    public function centers()
    {
        return $this->belongsToMany(Center::class, 'center_teachers');
    }

    public function experiences()
    {
        return $this->belongsToMany(Experience::class, 'experience_teachers');
    }
}

// Amenity
namespace App\Models;

class Amenity extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'image_url'];

    public function experiences()
    {
        return $this->belongsToMany(Experience::class, 'experience_amenities');
    }
}

// CenterCommission
namespace App\Models;

class CenterCommission extends Model
{
    protected $fillable = [
        'center_id',
        'commission',
        'deposit_policy',
        'deposit_amount',
        'cancellation_policy_condition',
        'cancellation_policy_days',
        'rest_of_payment',
        'rest_of_payment_days',
        'tax',
        'duration',
    ];

    public function center()
    {
        return $this->belongsTo(Center::class);
    }
}

// PayoutAccount
namespace App\Models;

class PayoutAccount extends Model
{
    protected $fillable = [
        'center_id',
        'account_holder_name',
        'bank_name',
        'account_number',
        'ifsc_code',
        'preferred_payout_cycle',
        'upi_id',
        'is_verified',
    ];

    public function center()
    {
        return $this->belongsTo(Center::class);
    }
}

// ExperienceImage
namespace App\Models;

class ExperienceImage extends Model
{
    protected $table = 'experience_image_gallery';

    protected $fillable = [
        'experience_id',
        'image_title',
        'image_url',
    ];

    public function experience()
    {
        return $this->belongsTo(Experience::class);
    }
}

// CenterImage
namespace App\Models;

class CenterImage extends Model
{
    protected $table = 'center_image_gallery';

    protected $fillable = [
        'center_id',
        'image_title',
        'image_url',
    ];

    public function center()
    {
        return $this->belongsTo(Center::class);
    }
}

// BookingUserInfo
namespace App\Models;

class BookingUserInfo extends Model
{
    protected $fillable = [
        'booking_id',
        'firstname',
        'lastname',
        'email',
        'phone',
        'message',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}

// BookingTransactionInfo
namespace App\Models;

class BookingTransactionInfo extends Model
{
    protected $fillable = [
        'booking_id',
        'tracking_id',
        'bank_ref_no',
        'order_status',
        'failure_message',
        'payment_mode',
        'card_name',
        'status_code',
        'status_message',
        'currency',
        'amount',
        'vault',
        'offer_type',
        'offer_code',
        'discount_value',
        'mer_amount',
        'eci_value',
        'retry',
        'response_code',
        'billing_notes',
        'trans_date',
    ];

    protected $casts = [
        'trans_date' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}

// BookingTransactionAddressInfo
namespace App\Models;

class BookingTransactionAddressInfo extends Model
{
    protected $fillable = [
        'booking_id',
        'billing_name',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_zip',
        'billing_country',
        'billing_tel',
        'billing_email',
        'delivery_name',
        'delivery_address',
        'delivery_city',
        'delivery_state',
        'delivery_zip',
        'delivery_country',
        'delivery_tel',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}

// ExperienceAccommodationImage
namespace App\Models;

class ExperienceAccommodationImage extends Model
{
    protected $table = 'experience_accomodation_image_gallery';

    protected $fillable = [
        'experience_id',
        'accomodation_id',
        'image_title',
        'image_url',
    ];
}

?>
