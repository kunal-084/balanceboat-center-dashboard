<?php
/**
 * BalanceBoat - Complete Form Request Implementations
 * Copy these files to app/Http/Requests/ directory
 */

// ============================================================================
// STORE RETREAT REQUEST - app/Http/Requests/StoreRetreatRequest.php
// ============================================================================

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRetreatRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:experiences,name',
            'experience_summary' => 'required|string|min:50|max:2000',
            'experience_category' => 'required|string|max:255',
            'price_per_person' => 'required|numeric|min:100',
            'currency' => 'required|in:INR,USD,EUR,GBP',
            'batch_size' => 'nullable|integer|min:1|max:100',
            'start_date_time' => 'required|date|after_or_equal:today',
            'end_date_time' => 'required|date|after:start_date_time',
            'duration' => 'required|string',
            'is_full_day_event' => 'boolean',
            'is_recurring' => 'boolean',
            'what_is_included' => 'nullable|string|max:2000',
            'what_is_not_included' => 'nullable|string|max:2000',
            'experience_highlights' => 'required|string|min:50|max:2000',
            'cancellation_policy' => 'nullable|string|max:3000',
            'deposit_policy' => 'boolean',
            'deposit_amount' => 'nullable|numeric|min:0',
            'early_bird_discount' => 'nullable|numeric|min:0|max:100',
            'early_bird_days' => 'nullable|integer|min:1',
            'accommodations' => 'required|array|min:1',
            'accommodations.*' => 'integer|exists:experience_accomodations,id',
            'teachers' => 'nullable|array',
            'teachers.*' => 'integer|exists:teachers,id',
            'amenities' => 'nullable|array',
            'amenities.*' => 'integer|exists:amenities,id',
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'video_url' => 'nullable|url',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Retreat name is required',
            'name.unique' => 'A retreat with this name already exists',
            'price_per_person.required' => 'Base price is required',
            'price_per_person.min' => 'Price must be at least 100',
            'start_date_time.after_or_equal' => 'Start date cannot be in the past',
            'end_date_time.after' => 'End date must be after start date',
            'experience_summary.min' => 'Summary must be at least 50 characters',
            'accommodations.min' => 'At least one accommodation must be assigned',
            'banner_image.image' => 'Banner must be a valid image',
            'banner_image.max' => 'Banner image must not exceed 2MB',
        ];
    }

    public function validated()
    {
        return array_merge(parent::validated(), [
            'is_draft' => true,
            'is_bookable' => false,
        ]);
    }
}

// ============================================================================
// UPDATE RETREAT REQUEST - app/Http/Requests/UpdateRetreatRequest.php
// ============================================================================

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRetreatRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        $retreatId = $this->route('retreat')->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('experiences', 'name')->ignore($retreatId),
            ],
            'experience_summary' => 'required|string|min:50|max:2000',
            'experience_category' => 'required|string|max:255',
            'price_per_person' => 'required|numeric|min:100',
            'currency' => 'required|in:INR,USD,EUR,GBP',
            'batch_size' => 'nullable|integer|min:1|max:100',
            'start_date_time' => 'required|date',
            'end_date_time' => 'required|date|after:start_date_time',
            'duration' => 'required|string',
            'is_full_day_event' => 'boolean',
            'is_recurring' => 'boolean',
            'what_is_included' => 'nullable|string|max:2000',
            'what_is_not_included' => 'nullable|string|max:2000',
            'experience_highlights' => 'required|string|min:50|max:2000',
            'cancellation_policy' => 'nullable|string|max:3000',
            'deposit_policy' => 'boolean',
            'deposit_amount' => 'nullable|numeric|min:0',
            'early_bird_discount' => 'nullable|numeric|min:0|max:100',
            'early_bird_days' => 'nullable|integer|min:1',
            'accommodations' => 'required|array|min:1',
            'accommodations.*' => 'integer|exists:experience_accomodations,id',
            'teachers' => 'nullable|array',
            'teachers.*' => 'integer|exists:teachers,id',
            'amenities' => 'nullable|array',
            'amenities.*' => 'integer|exists:amenities,id',
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'video_url' => 'nullable|url',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Retreat name is required',
            'name.unique' => 'A retreat with this name already exists',
            'experience_summary.min' => 'Summary must be at least 50 characters',
            'end_date_time.after' => 'End date must be after start date',
        ];
    }
}

// ============================================================================
// STORE PRICING REQUEST - app/Http/Requests/StorePricingRequest.php
// ============================================================================

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePricingRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'pricing_type' => 'required|in:duration,seasonal,occupancy,promotional',
            'duration' => 'required_if:pricing_type,duration|integer|min:1',
            'price' => 'required|numeric|min:0.01',
            'promo_price' => 'nullable|numeric|min:0|lt:price',
            'currency' => 'required|string|size:3',
            'start_date' => 'required_if:pricing_type,seasonal,occupancy|date',
            'end_date' => 'required_if:pricing_type,seasonal,occupancy|date|after:start_date',
            'accommodation_id' => 'required_if:pricing_type,occupancy|integer|exists:experience_accomodations,id',
            'min_occupancy' => 'nullable|integer|min:1',
            'max_occupancy' => 'nullable|integer|min:1',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
        ];
    }

    public function messages()
    {
        return [
            'price.required' => 'Price is required',
            'price.min' => 'Price must be greater than 0',
            'promo_price.lt' => 'Promo price must be less than regular price',
            'end_date.after' => 'End date must be after start date',
            'duration.required_if' => 'Duration is required for duration-based pricing',
            'accommodation_id.required_if' => 'Accommodation is required for occupancy pricing',
        ];
    }
}

// ============================================================================
// STORE BOOKING REQUEST - app/Http/Requests/StoreBookingRequest.php
// ============================================================================

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Guests can make bookings
    }

    public function rules()
    {
        return [
            'experience_id' => 'required|integer|exists:experiences,id',
            'accommodation_id' => 'required|integer|exists:experience_accomodations,id',
            'arrival_date' => 'required|date|after_or_equal:today',
            'departure_date' => 'required|date|after:arrival_date',
            'guest_count' => 'required|integer|min:1|max:20',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|regex:/^[0-9\+\-\s]{10,}$/',
            'message' => 'nullable|string|max:1000',
            'coupon_code' => 'nullable|string|max:50',
            'billing_name' => 'nullable|string|max:255',
            'billing_address' => 'nullable|string|max:500',
            'billing_city' => 'nullable|string|max:100',
            'billing_state' => 'nullable|string|max:100',
            'billing_zip' => 'nullable|string|max:20',
            'billing_country' => 'nullable|string|max:100',
            'billing_email' => 'nullable|email',
            'billing_tel' => 'nullable|string|max:20',
            'agree_terms' => 'required|accepted',
        ];
    }

    public function messages()
    {
        return [
            'experience_id.exists' => 'The selected retreat does not exist',
            'accommodation_id.exists' => 'The selected accommodation does not exist',
            'arrival_date.after_or_equal' => 'Arrival date cannot be in the past',
            'departure_date.after' => 'Departure date must be after arrival date',
            'guest_count.min' => 'At least one guest is required',
            'guest_count.max' => 'Maximum 20 guests per booking',
            'email.email' => 'Please provide a valid email address',
            'phone.regex' => 'Please provide a valid phone number',
            'billing_email.email' => 'Please provide a valid billing email',
            'agree_terms.required' => 'You must agree to the terms and conditions',
        ];
    }
}

// ============================================================================
// UPDATE ACCOUNT REQUEST - app/Http/Requests/UpdateAccountRequest.php
// ============================================================================

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'center_name' => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'contact_person' => 'required|string|max:255',
            'year_of_foundation' => 'nullable|integer|min:1800|max:' . date('Y'),
            'email_address' => 'required|email|max:255',
            'phone_number' => 'required|string|regex:/^[0-9\+\-\s]{10,}$/',
            'whatsapp_number' => 'nullable|string|regex:/^[0-9\+\-\s]{10,}$/',
            'website' => 'nullable|url',
            'facebook_url' => 'nullable|url',
            'instagram_url' => 'nullable|url',
            'billing_address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'gst_number' => 'nullable|string|regex:/^[A-Z0-9]{15}$/',
            'pan_number' => 'nullable|string|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
            'account_holder_name' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|regex:/^[0-9]{8,17}$/',
            'ifsc_code' => 'nullable|string|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/',
            'upi_id' => 'nullable|string|regex:/^[\w.-]+@[\w.-]+$/',
            'preferred_payout_cycle' => 'nullable|in:weekly,bi-weekly,monthly',
        ];
    }

    public function messages()
    {
        return [
            'center_name.required' => 'Center name is required',
            'contact_person.required' => 'Contact person name is required',
            'email_address.email' => 'Please provide a valid email',
            'phone_number.regex' => 'Please provide a valid phone number',
            'gst_number.regex' => 'Please provide a valid GST number (15 characters)',
            'pan_number.regex' => 'Please provide a valid PAN number',
            'account_number.regex' => 'Please provide a valid bank account number',
            'ifsc_code.regex' => 'Please provide a valid IFSC code',
            'upi_id.regex' => 'Please provide a valid UPI ID',
        ];
    }
}

// ============================================================================
// STORE ACCOMMODATION REQUEST - app/Http/Requests/StoreAccommodationRequest.php
// ============================================================================

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccommodationRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'about' => 'nullable|string|max:1000',
            'price_per_night_per_guest' => 'required|numeric|min:0.01',
            'currency' => 'required|in:INR,USD,EUR,GBP',
            'max_guest_in_room' => 'required|integer|min:1|max:20',
            'accommodation_default' => 'boolean',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Accommodation title is required',
            'price_per_night_per_guest.required' => 'Price per night is required',
            'price_per_night_per_guest.min' => 'Price must be greater than 0',
            'max_guest_in_room.required' => 'Maximum guests is required',
            'max_guest_in_room.min' => 'At least 1 guest must be allowed',
            'gallery_images.*.image' => 'Each file must be a valid image',
            'gallery_images.*.max' => 'Each image must not exceed 2MB',
        ];
    }
}

// ============================================================================
// CREATE SCHEDULE REQUEST - app/Http/Requests/CreateScheduleRequest.php
// ============================================================================

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateScheduleRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'schedules' => 'required|array|min:1',
            'schedules.*.day' => 'required|string|max:50',
            'schedules.*.start_time' => 'nullable|date_format:H:i',
            'schedules.*.end_time' => 'nullable|date_format:H:i',
            'schedules.*.activity' => 'required|string|max:1000',
        ];
    }

    public function messages()
    {
        return [
            'schedules.required' => 'At least one schedule day is required',
            'schedules.*.activity.required' => 'Activity description is required for each day',
            'schedules.*.start_time.date_format' => 'Start time must be in HH:mm format',
            'schedules.*.end_time.date_format' => 'End time must be in HH:mm format',
        ];
    }
}

// ============================================================================
// CREATE RECURRING REQUEST - app/Http/Requests/CreateRecurringRequest.php
// ============================================================================

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRecurringRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'recurring_type' => 'required|in:Daily,Weekly,Monthly,Yearly',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'separation_count' => 'required|integer|min:1',
            'max_occurrences' => 'nullable|integer|min:1',
            'day_of_week' => 'nullable|required_if:recurring_type,Weekly|array',
            'day_of_week.*' => 'string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'day_of_month' => 'nullable|required_if:recurring_type,Monthly|integer|min:1|max:31',
            'month_of_year' => 'nullable|required_if:recurring_type,Yearly|date_format:Y-m-d',
        ];
    }

    public function messages()
    {
        return [
            'recurring_type.required' => 'Recurrence type is required',
            'start_date.required' => 'Start date is required',
            'end_date.after' => 'End date must be after start date',
            'day_of_week.required_if' => 'At least one day of week must be selected',
            'day_of_month.required_if' => 'Day of month is required for monthly recurrence',
            'month_of_year.required_if' => 'Date is required for yearly recurrence',
        ];
    }
}

?>
