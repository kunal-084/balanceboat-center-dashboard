<?php
/**
 * BalanceBoat - Routes and Migrations
 * Copy route files to routes/ directory
 * Copy migration files to database/migrations/ directory
 */

// ============================================================================
// ROUTES WEB - routes/web.php
// ============================================================================

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\{OverviewController, AccountController};
use App\Http\Controllers\Retreat\{RetreatController, RetreatPricingController};
use App\Http\Controllers\Booking\BookingController;

Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/', OverviewController::class . '@index')->name('dashboard');

    Route::prefix('dashboard')->group(function () {
        // Account
        Route::get('/account', AccountController::class . '@show')->name('account.show');
        Route::patch('/account', AccountController::class . '@update')->name('account.update');

        // Retreats
        Route::resource('retreat', RetreatController::class);
        Route::patch('retreat/{retreat}/publish', RetreatController::class . '@publish')->name('retreat.publish');
        Route::patch('retreat/{retreat}/draft', RetreatController::class . '@draft')->name('retreat.draft');
        Route::post('retreat/{retreat}/duplicate', RetreatController::class . '@duplicate')->name('retreat.duplicate');

        // Retreat Pricing
        Route::get('retreat/{retreat}/pricing', RetreatPricingController::class . '@index')->name('retreat.pricing.index');
        Route::post('retreat/{retreat}/pricing', RetreatPricingController::class . '@store')->name('retreat.pricing.store');

        // Bookings
        Route::resource('booking', BookingController::class)->only(['index', 'show']);
        Route::post('booking/{booking}/confirm', BookingController::class . '@confirm')->name('booking.confirm');
        Route::post('booking/{booking}/cancel', BookingController::class . '@cancel')->name('booking.cancel');
    });

    // Public booking
    Route::post('/booking', BookingController::class . '@store')->name('booking.store');
});

// Public routes for bookings
Route::get('/retreat/{retreat}/book/preview', BookingController::class . '@preview')->name('booking.preview');
Route::post('/retreat/{retreat}/pricing/calculate', RetreatPricingController::class . '@calculatePrice')->name('retreat.pricing.calculate');

// Fallback
Route::redirect('/', '/dashboard');

// ============================================================================
// ROUTES API - routes/api.php
// ============================================================================

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Pricing API
    Route::post('/pricing/calculate', 'App\Http\Controllers\Api\PricingController@calculate');

    // Availability API
    Route::get('/availability/calendar', 'App\Http\Controllers\Api\AvailabilityController@calendar');

    // Retreat API
    Route::get('/retreat/{retreat}/summary', 'App\Http\Controllers\Api\RetreatController@summary');
    Route::get('/retreat/{retreat}/availability', 'App\Http\Controllers\Api\RetreatController@availability');
});

// ============================================================================
// MIGRATION: CREATE USERS TABLE
// ============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 60);
            $table->string('last_name', 60);
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone_number', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('street_address', 255)->nullable();
            $table->string('city', 45)->nullable();
            $table->string('zipcode', 10)->nullable();
            $table->string('country', 45)->nullable();
            $table->string('profile_image_url', 255)->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index('email');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};

// ============================================================================
// MIGRATION: CREATE CENTERS TABLE
// ============================================================================

return new class extends Migration {
    public function up()
    {
        Schema::create('centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name', 255);
            $table->string('slug', 100)->unique();
            $table->string('business_name', 255)->nullable();
            $table->string('location', 255)->nullable();
            $table->string('city', 60)->nullable();
            $table->string('country', 200)->nullable();
            $table->string('address_of_center', 255)->nullable();
            $table->string('email_address', 255)->nullable();
            $table->string('contact_number', 100)->nullable();
            $table->string('whatsapp_number', 100)->nullable();
            $table->string('website', 250)->nullable();
            $table->string('facebook_url', 250)->nullable();
            $table->string('instagram_url', 250)->nullable();
            $table->string('gst_number', 15)->nullable();
            $table->string('pan_number', 10)->nullable();
            $table->mediumText('about_center')->nullable();
            $table->longText('what_sets_us_apart')->nullable();
            $table->longText('our_philosophy')->nullable();
            $table->longText('our_mission')->nullable();
            $table->mediumText('center_highlights')->nullable();
            $table->string('video_url', 255)->nullable();
            $table->string('banner_image_url', 255)->nullable();
            $table->integer('year_of_foundation')->nullable();
            $table->tinyText('founders')->nullable();
            $table->string('speciality_id', 100)->nullable();
            $table->string('category_id', 255)->nullable();
            $table->enum('have_accommodation', ['Yes', 'No'])->nullable();
            $table->boolean('is_draft')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('slug');
            $table->index('is_draft');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('centers');
    }
};

// ============================================================================
// MIGRATION: CREATE EXPERIENCES TABLE (RETREATS)
// ============================================================================

return new class extends Migration {
    public function up()
    {
        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('centers')->onDelete('cascade');
            $table->string('name', 255);
            $table->string('slug', 100)->unique();
            $table->longText('experience_summary')->nullable();
            $table->longText('experience_highlights')->nullable();
            $table->dateTime('start_date_time')->nullable();
            $table->dateTime('end_date_time')->nullable();
            $table->string('duration', 100)->nullable();
            $table->decimal('price_per_person', 10, 2)->nullable();
            $table->string('currency', 3)->default('INR');
            $table->smallInteger('early_bird_days')->nullable();
            $table->decimal('early_bird_discount', 5, 2)->nullable();
            $table->integer('batch_size')->nullable();
            $table->boolean('is_full_day_event')->default(false);
            $table->boolean('is_recurring')->default(false);
            $table->boolean('is_bookable')->default(false);
            $table->boolean('is_draft')->default(true);
            $table->text('what_is_included')->nullable();
            $table->text('what_is_not_included')->nullable();
            $table->string('banner_image_url', 255)->nullable();
            $table->string('video_url', 255)->nullable();
            $table->longText('cancellation_policy')->nullable();
            $table->boolean('deposit_policy')->default(false);
            $table->decimal('deposit_amount', 10, 2)->nullable();
            $table->string('experience_category', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('center_id');
            $table->index('slug');
            $table->index('start_date_time');
            $table->index('is_draft');
            $table->index('is_bookable');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('experiences');
    }
};

// ============================================================================
// MIGRATION: CREATE ACCOMMODATIONS TABLE
// ============================================================================

return new class extends Migration {
    public function up()
    {
        Schema::create('accomodations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->nullable();
            $table->longText('description')->nullable();
            $table->integer('max_guest_in_room')->nullable();
            $table->string('banner_image_url', 255)->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('accomodations');
    }
};

// ============================================================================
// MIGRATION: CREATE EXPERIENCE ACCOMMODATIONS TABLE
// ============================================================================

return new class extends Migration {
    public function up()
    {
        Schema::create('experience_accomodations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experience_id')->constrained('experiences')->onDelete('cascade');
            $table->foreignId('accommodation_id')->nullable()->constrained('accomodations')->onDelete('set null');
            $table->string('title', 100)->default('Standard');
            $table->mediumText('about')->nullable();
            $table->decimal('price_per_night_per_guest', 10, 2)->nullable();
            $table->string('currency', 3)->default('INR');
            $table->integer('max_guest_in_room')->nullable();
            $table->boolean('accommodation_default')->default(false);
            $table->timestamps();

            $table->index('experience_id');
            $table->index('accommodation_default');
        });
    }

    public function down()
    {
        Schema::dropIfExists('experience_accomodations');
    }
};

// ============================================================================
// MIGRATION: CREATE BOOKINGS TABLE
// ============================================================================

return new class extends Migration {
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experience_id')->constrained('experiences')->onDelete('cascade');
            $table->foreignId('experience_accomodation_id')->nullable()->constrained('experience_accomodations')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('arrival_date');
            $table->dateTime('start_date_time')->nullable();
            $table->dateTime('end_date_time')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('guest_count')->default(1);
            $table->decimal('price_per_person', 10, 2);
            $table->decimal('booking_amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('commission_amount', 10, 2)->default(0);
            $table->decimal('pay_amount', 10, 2);
            $table->string('currency', 3)->default('INR');
            $table->enum('order_status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('transaction_id', 60)->nullable();
            $table->boolean('is_full_day_event')->default(false);
            $table->boolean('is_recurring')->default(false);
            $table->timestamps();

            $table->index('experience_id');
            $table->index('user_id');
            $table->index('arrival_date');
            $table->index('order_status');
            $table->index('payment_status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookings');
    }
};

// ============================================================================
// MIGRATION: CREATE EXPERIENCE SCHEDULES TABLE
// ============================================================================

return new class extends Migration {
    public function up()
    {
        Schema::create('experience_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experience_id')->constrained('experiences')->onDelete('cascade');
            $table->text('schedule_day');
            $table->dateTime('schedule_start_time')->nullable();
            $table->dateTime('schedule_end_time')->nullable();
            $table->text('activity_description')->nullable();
            $table->timestamps();

            $table->index('experience_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('experience_schedules');
    }
};

// ============================================================================
// MIGRATION: CREATE EXPERIENCE RECURRING TABLE
// ============================================================================

return new class extends Migration {
    public function up()
    {
        Schema::create('experience_recurring', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experience_id')->constrained('experiences')->onDelete('cascade');
            $table->enum('recurring_type', ['Daily', 'Weekly', 'Monthly', 'Yearly'])->default('Weekly');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->integer('separation_count')->nullable();
            $table->integer('max_num_of_occurrances')->nullable();
            $table->string('day_of_week', 200)->nullable();
            $table->string('week_of_month', 200)->nullable();
            $table->string('day_of_month', 200)->nullable();
            $table->string('month_of_year', 200)->nullable();
            $table->date('recurring_end_date')->nullable();
            $table->boolean('is_cancelled')->default(false);
            $table->timestamps();

            $table->index('experience_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('experience_recurring');
    }
};

?>
