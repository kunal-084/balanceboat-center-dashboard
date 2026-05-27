# BalanceBoat - Database Design, Setup & Deployment Guide

## Part 1: Complete Database Design & Relationships

### Entity Relationship Diagram (ERD)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          BALANCEBOAT DATABASE SCHEMA                        │
└─────────────────────────────────────────────────────────────────────────────┘

USERS & AUTHENTICATION
├── users (id, first_name, last_name, email, password, phone_number, ...)
└── model_has_roles (user_id, role_id)

CENTERS (Multi-tenant)
├── centers (id, name, user_id, email_address, contact_number, ...)
│   ├── center_accommodations (center_id, accommodation_id)
│   ├── center_teachers (center_id, teacher_id)
│   ├── center_image_gallery (center_id, image_url)
│   ├── center_commissions (center_id, commission%, tax_rate)
│   ├── payout_accounts (center_id, bank_details)
│   └── center_features (center_id, feature_list)
│
└── EXPERIENCES/RETREATS (Core)
    ├── experiences (id, center_id, name, price, dates, ...)
    │   ├── experience_category (experience_id, category_id)
    │   ├── experience_teachers (experience_id, teacher_id)
    │   ├── experience_amenities (experience_id, amenity_id)
    │   │
    │   ├── ACCOMMODATIONS FOR RETREAT
    │   ├── experience_accomodations (id, experience_id, accommodation_id, max_guests, ...)
    │   │   ├── experience_accomodation_prices (exp_acc_id, start_date, end_date, price)
    │   │   ├── experience_accomodation_image_gallery (exp_acc_id, image_url)
    │   │   └── experience_accomodation_availability (exp_acc_id, date, available_count)
    │   │
    │   ├── SCHEDULING
    │   ├── experience_schedules (experience_id, schedule_day, activity, time)
    │   ├── experience_recurring (experience_id, recurring_type, pattern, dates)
    │   ├── experience_recurring_exception (experience_id, exception_date)
    │   ├── experience_recurring_manually (experience_id, start_date, end_date)
    │   │
    │   ├── PRICING
    │   ├── experience_duration_prices (experience_id, duration, price, currency)
    │   ├── dynamic_pricing_rules (experience_id, rule_type, conditions, discount%)
    │   ├── coupons (code, discount_value, valid_from, valid_until)
    │   │
    │   ├── GALLERIES & MEDIA
    │   ├── experience_image_gallery (experience_id, image_url)
    │   ├── experience_food_image_gallery (experience_id, image_url)
    │   │
    │   ├── REVIEWS & RATINGS
    │   └── reviews (experience_id, user_id, rating, content, source)
    │
    └── BOOKINGS
        ├── bookings (id, experience_id, exp_acc_id, user_id, arrival_date, ...)
        ├── booking_user_info (booking_id, first_name, email, phone)
        ├── booking_transaction_info (booking_id, transaction_id, payment_status)
        ├── booking_transaction_address_info (booking_id, billing_address, ...)
        └── booking_experience_info (booking_id, experience_details_snapshot)

REFERENCE DATA
├── accommodations (id, name, description, max_guests)
│   └── accommodation_image_gallery
├── categories (id, name, description, image_url)
├── amenities (id, name, description, image_url)
├── teachers (id, name, expertise_id, profile_image)
├── expertise (id, name)
└── currencies (id, code, symbol, exchange_rate)
```

### Core Tables Definition

```sql
-- USERS TABLE
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(60),
    last_name VARCHAR(60),
    email VARCHAR(191) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20),
    email_verified_at TIMESTAMP NULL,
    date_of_birth DATE,
    street_address VARCHAR(255),
    city VARCHAR(45),
    zipcode VARCHAR(10),
    country VARCHAR(45),
    profile_image_url VARCHAR(255),
    remember_token VARCHAR(100),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    KEY idx_email (email),
    KEY idx_created_at (created_at)
);

-- CENTERS TABLE
CREATE TABLE centers (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) UNIQUE,
    business_name VARCHAR(255),
    location VARCHAR(255),
    city VARCHAR(60),
    country VARCHAR(200),
    address_of_center VARCHAR(255),
    email_address VARCHAR(255),
    contact_number VARCHAR(100),
    whatsapp_number VARCHAR(100),
    phone_number VARCHAR(20),
    website VARCHAR(250),
    facebook_url VARCHAR(250),
    instagram_url VARCHAR(250),
    gst_number VARCHAR(15),
    pan_number VARCHAR(10),
    about_center MEDIUMTEXT,
    what_sets_us_apart LONGTEXT,
    our_philosophy LONGTEXT,
    our_mission LONGTEXT,
    center_highlights MEDIUMTEXT,
    video_url VARCHAR(255),
    banner_image_url VARCHAR(255),
    year_of_foundation INT,
    founders TINYTEXT,
    speciality_id VARCHAR(100),
    category_id VARCHAR(255),
    have_accommodation ENUM('Yes','No'),
    is_draft TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_centers_user FOREIGN KEY (user_id) REFERENCES users(id),
    KEY idx_name (name),
    KEY idx_slug (slug),
    KEY idx_is_draft (is_draft),
    KEY idx_created_at (created_at)
);

-- EXPERIENCES TABLE (RETREATS)
CREATE TABLE experiences (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    center_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) UNIQUE,
    experience_summary LONGTEXT,
    experience_highlights LONGTEXT,
    price_per_person DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'INR',
    batch_size INT,
    start_date_time DATETIME,
    end_date_time DATETIME,
    duration VARCHAR(100),
    is_full_day_event TINYINT(1) DEFAULT 0,
    is_recurring TINYINT(1) DEFAULT 0,
    is_bookable TINYINT(1) DEFAULT 0,
    is_draft TINYINT(1) DEFAULT 1,
    what_is_included TEXT,
    what_is_not_included MEDIUMTEXT,
    banner_image_url VARCHAR(255),
    video_url VARCHAR(255),
    cancellation_policy LONGTEXT,
    deposit_policy TINYINT(1) DEFAULT 0,
    deposit_amount DECIMAL(10,2),
    early_bird_days SMALLINT,
    early_bird_discount DECIMAL(5,2),
    experience_category VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_experiences_center FOREIGN KEY (center_id) REFERENCES centers(id),
    KEY idx_center_id (center_id),
    KEY idx_slug (slug),
    KEY idx_start_date_time (start_date_time),
    KEY idx_is_draft (is_draft),
    KEY idx_is_bookable (is_bookable),
    KEY idx_created_at (created_at)
);

-- ACCOMMODATIONS TABLE
CREATE TABLE accomodations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100),
    description LONGTEXT,
    max_guest_in_room INT,
    banner_image_url VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    KEY idx_name (name)
);

-- EXPERIENCE ACCOMMODATIONS TABLE (Bridge with Pricing)
CREATE TABLE experience_accomodations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    experience_id BIGINT NOT NULL,
    accommodation_id BIGINT,
    title VARCHAR(100),
    about MEDIUMTEXT,
    price_per_night_per_guest DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'INR',
    max_guest_in_room INT,
    accommodation_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_exp_acc_experience FOREIGN KEY (experience_id) REFERENCES experiences(id),
    CONSTRAINT fk_exp_acc_accommodation FOREIGN KEY (accommodation_id) REFERENCES accomodations(id),
    KEY idx_experience_id (experience_id),
    KEY idx_accommodation_default (accommodation_default)
);

-- EXPERIENCE ACCOMMODATION PRICES TABLE (Occupancy & Seasonal Pricing)
CREATE TABLE experience_accomodation_prices (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    experience_id BIGINT NOT NULL,
    accomodation_id BIGINT NOT NULL,
    duration INT,
    start_date DATE,
    end_date DATE,
    avg_price DECIMAL(10,2),
    price_per_night_per_guest DECIMAL(10,2),
    promotional_price DECIMAL(10,2),
    promotional_discount DECIMAL(20,2),
    currency VARCHAR(3),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_exp_acc_price_exp FOREIGN KEY (experience_id) REFERENCES experiences(id),
    KEY idx_dates (start_date, end_date),
    KEY idx_experience_accommodation (experience_id, accomodation_id)
);

-- EXPERIENCE DURATION PRICES TABLE
CREATE TABLE experience_duration_prices (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    experience_id BIGINT NOT NULL,
    duration INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    promo_price DECIMAL(10,2),
    currency VARCHAR(3),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_exp_dur_price FOREIGN KEY (experience_id) REFERENCES experiences(id),
    UNIQUE KEY unique_duration (experience_id, duration),
    KEY idx_experience_id (experience_id)
);

-- EXPERIENCE SCHEDULES TABLE (Day-wise Itinerary)
CREATE TABLE experience_schedules (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    experience_id BIGINT NOT NULL,
    schedule_day TEXT,
    schedule_start_time DATETIME,
    schedule_end_time DATETIME,
    activity_description TEXT,
    day_order INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_exp_schedule FOREIGN KEY (experience_id) REFERENCES experiences(id),
    KEY idx_experience_id (experience_id)
);

-- EXPERIENCE RECURRING TABLE
CREATE TABLE experience_recurring (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    experience_id BIGINT NOT NULL,
    recurring_type ENUM('Daily','Weekly','Monthly','Yearly') DEFAULT 'Weekly',
    start_date DATETIME,
    end_date DATETIME,
    separation_count INT,
    max_num_of_occurrances INT,
    day_of_week VARCHAR(200),
    week_of_month VARCHAR(200),
    day_of_month VARCHAR(200),
    month_of_year VARCHAR(200),
    recurring_end_date DATE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_exp_recurring FOREIGN KEY (experience_id) REFERENCES experiences(id),
    KEY idx_experience_id (experience_id)
);

-- BOOKINGS TABLE
CREATE TABLE bookings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    experience_id BIGINT NOT NULL,
    experience_accomodation_id BIGINT,
    user_id INT,
    arrival_date DATE,
    start_date_time DATETIME,
    end_date_time DATETIME,
    duration INT,
    guest_count INT DEFAULT 1,
    price_per_person DECIMAL(10,2),
    booking_amount DECIMAL(10,2),
    discount_amount DECIMAL(10,2) DEFAULT 0,
    commission_amount DECIMAL(10,2) DEFAULT 0,
    pay_amount DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'INR',
    order_status VARCHAR(100) DEFAULT 'pending',
    payment_status VARCHAR(100) DEFAULT 'pending',
    transaction_id VARCHAR(60),
    is_full_day_event TINYINT(1) DEFAULT 0,
    is_recurring TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_booking_experience FOREIGN KEY (experience_id) REFERENCES experiences(id),
    CONSTRAINT fk_booking_exp_acc FOREIGN KEY (experience_accomodation_id) REFERENCES experience_accomodations(id),
    CONSTRAINT fk_booking_user FOREIGN KEY (user_id) REFERENCES users(id),
    KEY idx_experience_id (experience_id),
    KEY idx_user_id (user_id),
    KEY idx_arrival_date (arrival_date),
    KEY idx_order_status (order_status),
    KEY idx_payment_status (payment_status)
);

-- REVIEWS TABLE
CREATE TABLE reviews (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    experience_id BIGINT,
    center_id BIGINT,
    user_id INT,
    booking_id BIGINT,
    rating DECIMAL(2,1),
    title VARCHAR(255),
    content LONGTEXT,
    source VARCHAR(100),
    verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_review_experience FOREIGN KEY (experience_id) REFERENCES experiences(id),
    CONSTRAINT fk_review_center FOREIGN KEY (center_id) REFERENCES centers(id),
    KEY idx_experience_id (experience_id),
    KEY idx_rating (rating),
    KEY idx_created_at (created_at)
);

-- AVAILABILITY TABLE
CREATE TABLE availability (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    experience_id BIGINT NOT NULL,
    accomodation_id BIGINT NOT NULL,
    available_date DATE,
    total_capacity INT,
    booked_count INT DEFAULT 0,
    available_count INT,
    price DECIMAL(10,2),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_avail_experience FOREIGN KEY (experience_id) REFERENCES experiences(id),
    UNIQUE KEY unique_date_accommodation (available_date, accomodation_id),
    KEY idx_available_date (available_date)
);

-- DYNAMIC PRICING RULES TABLE
CREATE TABLE dynamic_pricing_rules (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    experience_id BIGINT NOT NULL,
    rule_name VARCHAR(255),
    rule_type ENUM('early_bird','occupancy','seasonal','promotional') DEFAULT 'promotional',
    condition_type VARCHAR(100),
    condition_value VARCHAR(255),
    discount_type ENUM('percentage','fixed') DEFAULT 'percentage',
    discount_value DECIMAL(10,2),
    priority INT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_pricing_rule_exp FOREIGN KEY (experience_id) REFERENCES experiences(id),
    KEY idx_experience_id (experience_id),
    KEY idx_is_active (is_active)
);
```

---

## Part 2: Installation & Setup Guide

### Step 1: Environment Setup

```bash
# Clone the repository
git clone git@github.com:balanceboat/center-dashboard.git
cd center-dashboard

# Copy environment file
cp .env.example .env

# Update .env with your configuration
# KEY SETTINGS:
APP_NAME=BalanceBoat
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dashboard.balanceboat.com
DB_HOST=localhost
DB_DATABASE=balanceboat_center
DB_USERNAME=balanceboat_user
MAIL_MAILER=smtp
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis

# Install dependencies
composer install --no-dev --optimize-autoloader

# Generate application key
php artisan key:generate

# Install Node.js dependencies
npm install && npm run build
```

### Step 2: Database Setup

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE balanceboat_center CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php artisan migrate:fresh --seed

# This will:
# - Create all tables
# - Create indexes
# - Seed with demo data
# - Create admin user

# Verify database integrity
php artisan db:monitor
```

### Step 3: File Storage Setup

```bash
# Create storage directories
mkdir -p storage/app/public/retreats
mkdir -p storage/app/public/accommodations
mkdir -p storage/app/public/galleries
mkdir -p storage/app/private/reports

# Link public storage
php artisan storage:link

# Set permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

### Step 4: Queue Setup (for async jobs)

```bash
# Using Redis (recommended)
# .env file should have:
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Start queue worker
php artisan queue:work redis --tries=3 --backoff=60

# Or use supervisor for persistent workers
# Create /etc/supervisor/conf.d/balanceboat.conf
```

### Step 5: Search Engine Setup (Optional - Meilisearch for retreats)

```bash
# Install Meilisearch
composer require meilisearch/meilisearch-php

# Configure in .env
MEILISEARCH_HOST=http://localhost:7700
MEILISEARCH_KEY=your_api_key

# Index existing data
php artisan scout:import "App\Models\Experience"
```

---

## Part 3: Testing Strategy

### Unit Tests

```php
namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PricingEngine;
use App\Models\Experience;
use App\Models\ExperienceAccommodation;
use Carbon\Carbon;

class PricingEngineTest extends TestCase
{
    protected PricingEngine $pricingEngine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricingEngine = app()->make(PricingEngine::class);
    }

    /** @test */
    public function it_calculates_base_price_correctly()
    {
        $retreat = Experience::factory()->create(['price_per_person' => 10000]);
        
        $pricing = $this->pricingEngine->calculateBookingPrice(
            $retreat,
            $retreat->accommodations()->first(),
            Carbon::now()->addDays(5),
            Carbon::now()->addDays(10),
            2
        );

        $this->assertEquals(50000, $pricing['subtotal']); // 10000 * 5 nights
    }

    /** @test */
    public function it_applies_early_bird_discount()
    {
        $retreat = Experience::factory()->create([
            'price_per_person' => 10000,
            'early_bird_days' => 30,
            'early_bird_discount' => 20
        ]);

        $pricing = $this->pricingEngine->calculateBookingPrice(
            $retreat,
            $retreat->accommodations()->first(),
            Carbon::now()->addDays(10),
            Carbon::now()->addDays(15),
            1
        );

        $this->assertGreater($pricing['early_bird_discount'], 0);
    }

    /** @test */
    public function it_applies_occupancy_discount_for_groups()
    {
        $retreat = Experience::factory()->create(['price_per_person' => 10000]);
        
        $pricing = $this->pricingEngine->calculateBookingPrice(
            $retreat,
            $retreat->accommodations()->first(),
            Carbon::now()->addDays(5),
            Carbon::now()->addDays(10),
            10 // 10 guests - should get group discount
        );

        $this->assertGreater($pricing['occupancy_discount'], 0);
    }
}
```

### Feature Tests

```php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Center;
use App\Models\Experience;

class RetreatManagementTest extends TestCase
{
    protected User $user;
    protected Center $center;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->center = Center::factory()->create(['user_id' => $this->user->id]);
        $this->user->centers()->attach($this->center->id, ['role' => 'admin']);
    }

    /** @test */
    public function user_can_create_retreat()
    {
        $this->actingAs($this->user)
            ->post(route('retreat.store'), [
                'name' => 'Test Retreat',
                'experience_summary' => 'A wonderful 7-day yoga and meditation retreat',
                'experience_category' => 'yoga',
                'price_per_person' => 10000,
                'currency' => 'INR',
                'start_date_time' => now()->addDays(30)->format('Y-m-d H:i'),
                'end_date_time' => now()->addDays(37)->format('Y-m-d H:i'),
                'duration' => '7_days',
                'accommodations' => [1, 2],
                'what_is_included' => 'Yoga, meals, accommodation',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('experiences', [
            'name' => 'Test Retreat',
            'is_draft' => true
        ]);
    }

    /** @test */
    public function user_can_publish_retreat()
    {
        $retreat = Experience::factory()->for($this->center)->create(['is_draft' => true]);

        $this->actingAs($this->user)
            ->patch(route('retreat.publish', $retreat))
            ->assertRedirect();

        $this->assertTrue($retreat->fresh()->is_bookable);
    }

    /** @test */
    public function user_can_duplicate_retreat()
    {
        $retreat = Experience::factory()->for($this->center)->create();

        $this->actingAs($this->user)
            ->post(route('retreat.duplicate', $retreat))
            ->assertRedirect();

        $this->assertEquals(2, $this->center->experiences()->count());
    }
}
```

### Booking Integration Tests

```php
/** @test */
public function customer_can_complete_booking_flow()
{
    $retreat = Experience::factory()->create();
    $accommodation = $retreat->accommodations()->first();

    // Step 1: Preview booking with pricing
    $response = $this->get('/booking/preview', [
        'retreat_id' => $retreat->id,
        'accommodation_id' => $accommodation->id,
        'arrival_date' => now()->addDays(30)->format('Y-m-d'),
        'departure_date' => now()->addDays(37)->format('Y-m-d'),
        'guest_count' => 2,
    ]);

    $this->assertContains('pricing', $response->json());

    // Step 2: Create booking
    $response = $this->post(route('booking.store'), [
        'experience_id' => $retreat->id,
        'accommodation_id' => $accommodation->id,
        'arrival_date' => now()->addDays(30)->format('Y-m-d'),
        'departure_date' => now()->addDays(37)->format('Y-m-d'),
        'guest_count' => 2,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone' => '9876543210',
        'agree_terms' => true,
    ]);

    $booking = Booking::latest()->first();
    $this->assertEquals(Booking::STATUS_PENDING, $booking->order_status);
}
```

### Run Tests

```bash
# Run all tests
php artisan test

# Run specific test class
php artisan test --filter=PricingEngineTest

# With code coverage
php artisan test --coverage

# Generate HTML coverage report
php artisan test --coverage --coverage-html=coverage
```

---

## Part 4: Deployment Guide

### Pre-Deployment Checklist

```bash
# 1. Code Quality
./vendor/bin/phpstan analyse
./vendor/bin/pint --test
./vendor/bin/phpcs --standard=PSR12

# 2. Security Audit
composer audit
npm audit

# 3. Run Tests
php artisan test
php artisan test --coverage

# 4. Database Integrity Check
php artisan db:monitor
php artisan schema:validate

# 5. Build Assets
npm run build
```

### Docker Deployment (Recommended)

```dockerfile
# Dockerfile
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpq-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    git \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install pdo pdo_mysql gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
```

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build: .
    container_name: balanceboat_app
    ports:
      - "8000:8000"
    volumes:
      - .:/app
    environment:
      - DB_HOST=mysql
      - DB_DATABASE=balanceboat
      - DB_USERNAME=balanceboat
      - DB_PASSWORD=secret
      - REDIS_HOST=redis
    depends_on:
      - mysql
      - redis
    networks:
      - balanceboat

  mysql:
    image: mysql:8.0
    container_name: balanceboat_mysql
    ports:
      - "3306:3306"
    environment:
      MYSQL_DATABASE: balanceboat
      MYSQL_USER: balanceboat
      MYSQL_PASSWORD: secret
      MYSQL_ROOT_PASSWORD: secret
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - balanceboat

  redis:
    image: redis:7-alpine
    container_name: balanceboat_redis
    ports:
      - "6379:6379"
    networks:
      - balanceboat

  nginx:
    image: nginx:latest
    container_name: balanceboat_nginx
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf:ro
      - ./public:/app/public:ro
    depends_on:
      - app
    networks:
      - balanceboat

volumes:
  mysql_data:

networks:
  balanceboat:
```

### Production Deployment (AWS)

```bash
# 1. Set up RDS MySQL
aws rds create-db-instance \
  --db-instance-identifier balanceboat-prod \
  --db-instance-class db.t3.micro \
  --engine mysql \
  --master-username admin \
  --master-user-password SecurePassword123!

# 2. Create Elasticache Redis
aws elasticache create-cache-cluster \
  --cache-cluster-id balanceboat-redis \
  --cache-node-type cache.t3.micro \
  --engine redis

# 3. Deploy to ECS
# Create task definition
aws ecs register-task-definition --cli-input-json file://task-definition.json

# Create service
aws ecs create-service \
  --cluster balanceboat-prod \
  --service-name balanceboat-api \
  --task-definition balanceboat:1 \
  --desired-count 3 \
  --launch-type FARGATE

# 4. Set up CloudFront CDN
aws cloudfront create-distribution --distribution-config file://distribution.json
```

### Zero-Downtime Deployment

```bash
#!/bin/bash
# deploy.sh

set -e

echo "Starting deployment..."

# 1. Pull latest code
git pull origin main

# 2. Install dependencies (composer + npm)
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 3. Backup database
mysqldump balanceboat > backups/db_$(date +%Y%m%d_%H%M%S).sql

# 4. Run migrations with zero downtime
php artisan migrate --force --step

# 5. Clear caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Warm up cache
php artisan cache:warm

# 7. Restart queue workers gracefully
php artisan queue:restart

# 8. Run health checks
curl -f http://localhost/health || exit 1

echo "Deployment complete!"
```

### Health Check Endpoint

```php
namespace App\Http\Controllers;

class HealthController extends Controller
{
    public function check()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $status = collect($checks)->every(fn($check) => $check['status'] === 'ok') ? 200 : 503;

        return response()->json([
            'status' => $status === 200 ? 'healthy' : 'unhealthy',
            'timestamp' => now(),
            'checks' => $checks,
        ], $status);
    }

    private function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'ok', 'message' => 'Database connected'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkRedis()
    {
        try {
            Cache::store('redis')->put('health_check', true, 10);
            return ['status' => 'ok', 'message' => 'Redis connected'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkStorage()
    {
        try {
            Storage::disk('public')->put('health_check.txt', 'ok');
            Storage::disk('public')->delete('health_check.txt');
            return ['status' => 'ok', 'message' => 'Storage accessible'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkQueue()
    {
        // Check if queue workers are running
        $workersRunning = DB::table('jobs')->count() < 1000;
        return $workersRunning ? 
            ['status' => 'ok', 'message' => 'Queue healthy'] :
            ['status' => 'warning', 'message' => 'Queue backlog detected'];
    }
}
```

---

## Part 5: Monitoring & Performance Optimization

### Database Optimization

```php
// Optimize queries with eager loading
Experience::with([
    'center',
    'accommodations.prices',
    'bookings',
    'schedules',
    'reviews'
])->paginate(15);

// Create indexes for common queries
DB::statement('CREATE INDEX idx_center_is_draft ON experiences(center_id, is_draft)');
DB::statement('CREATE INDEX idx_arrival_status ON bookings(arrival_date, order_status)');
```

### Caching Strategy

```php
// Cache retreat summary for 1 hour
$summary = Cache::remember("retreat:{$retreat->id}:summary", 3600, function () use ($retreat) {
    return [
        'occupancy' => $retreat->occupancy_percentage,
        'revenue' => $retreat->bookings()->confirmed()->sum('pay_amount'),
        'ratings' => $retreat->average_rating,
    ];
});

// Cache pricing calculations for 30 minutes
$pricing = Cache::remember(
    "pricing:{$retreat->id}:{$accommodation->id}:{$date}",
    1800,
    fn() => $this->pricingEngine->calculateBookingPrice(...)
);
```

### Monitoring with Laravel Telescope (Dev)

```php
// config/telescope.php
'enabled' => env('TELESCOPE_ENABLED', true),

'storage' => [
    'database' => [
        'connection' => 'mysql',
    ],
],

'filter' => [
    'only_paths' => [
        '/dashboard/*',
        '/api/*',
    ],
],

'ignore_paths' => [
    'health',
    'telescope',
],
```

This comprehensive guide covers:
✅ Complete database design with relationships
✅ Setup instructions for development and production
✅ Testing strategies with examples
✅ Docker deployment setup
✅ Cloud deployment (AWS) examples
✅ Zero-downtime deployment script
✅ Health checks and monitoring
✅ Performance optimization tips

Ready for enterprise-scale deployment!
