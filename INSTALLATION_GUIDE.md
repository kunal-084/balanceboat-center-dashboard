# BalanceBoat Center Dashboard - Complete Implementation Guide

## 📋 Files Generated

This package contains all the complete, production-ready code for BalanceBoat:

### Generated Code Files:
1. **001_MODELS.php** - All 20+ Eloquent models with relationships
2. **002_SERVICES.php** - 5 core service classes (Pricing, Availability, Scheduling, Booking, Retreat)
3. **003_REQUESTS.php** - Form request validation classes
4. **004_CONTROLLERS.php** - Web controllers (Retreat, Booking, Account, Dashboard)
5. **005_ROUTES_MIGRATIONS.php** - Routes and migration files
6. **006_VIEWS_CONFIG.blade.php** - Blade views and configuration
7. Plus 5 architecture documents

---

## 🚀 Step-by-Step Installation

### Step 1: Create Laravel Project

```bash
# Create new Laravel 11 project
composer create-project laravel/laravel balanceboat-center

cd balanceboat-center

# Install required packages
composer require spatie/laravel-permission
composer require laravel/sanctum

# Publish config
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

### Step 2: Copy Generated Code

```bash
# Create directories
mkdir -p app/Http/Controllers/Retreat app/Http/Controllers/Booking app/Http/Controllers/Dashboard
mkdir -p app/Http/Requests
mkdir -p app/Services
mkdir -p resources/views/components
mkdir -p resources/views/retreat
mkdir -p resources/views/booking
mkdir -p resources/views/dashboard
mkdir -p database/migrations

# Copy Models (from 001_MODELS.php)
# Copy each namespace block to: app/Models/{ModelName}.php

# Copy Services (from 002_SERVICES.php)
# Copy each service class to: app/Services/{ServiceName}.php

# Copy Requests (from 003_REQUESTS.php)
# Copy each request class to: app/Http/Requests/{RequestName}.php

# Copy Controllers (from 004_CONTROLLERS.php)
# Copy each controller to appropriate app/Http/Controllers/ directory

# Copy Routes (from 005_ROUTES_MIGRATIONS.php)
# Replace routes/web.php with the provided route file

# Copy Migrations (from 005_ROUTES_MIGRATIONS.php)
# Copy each migration to database/migrations/ with timestamp prefix

# Copy Views (from 006_VIEWS_CONFIG.blade.php)
# Copy Blade files to resources/views/

# Copy config section (from 006_VIEWS_CONFIG.blade.php)
# Update config/database.php with the provided mysql config

# Copy .env (from 006_VIEWS_CONFIG.blade.php)
# Update your .env with the provided configuration
```

### Step 3: Database Setup

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE balanceboat_center CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php artisan migrate:fresh

# Seed demo data (optional)
php artisan db:seed

# Create roles and permissions
php artisan tinker
# Inside tinker:
> use Spatie\Permission\Models\Role;
> use Spatie\Permission\Models\Permission;
> Role::create(['name' => 'admin', 'guard_name' => 'web']);
> Role::create(['name' => 'center_admin', 'guard_name' => 'web']);
> Role::create(['name' => 'staff', 'guard_name' => 'web']);
> exit()
```

### Step 4: Authentication Setup

```bash
# Install Breeze for authentication (optional, use your preferred auth)
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install
npm run dev

# Create a test user
php artisan tinker
> use App\Models\User;
> User::create(['first_name' => 'Test', 'last_name' => 'User', 'email' => 'test@example.com', 'password' => bcrypt('password')]);
> exit()
```

### Step 5: Install Frontend Dependencies

```bash
npm install
npm run build

# For development
npm run dev
```

### Step 6: File Storage Setup

```bash
# Create storage directories
mkdir -p storage/app/public/retreats
mkdir -p storage/app/public/accommodations
mkdir -p storage/app/public/galleries

# Link public storage
php artisan storage:link

# Set permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

### Step 7: Queue Setup (For Background Jobs)

```bash
# Install Redis (optional, can use database queue)
# For macOS with Homebrew:
# brew install redis
# brew services start redis

# Update .env
# QUEUE_CONNECTION=redis

# Start queue worker
php artisan queue:work
```

---

## 🧪 Testing the Installation

### 1. Create Test Retreat

```bash
# Start development server
php artisan serve

# Visit http://localhost:8000
# Login with test@example.com / password

# Navigate to Retreats > Create Retreat
# Fill in the form and create a retreat
```

### 2. Create Test Booking

```bash
# As a guest (no login), visit:
# http://localhost:8000/retreat/{retreat_id}/book/preview

# Fill in booking details and complete the flow
```

### 3. Test Pricing Engine

```bash
php artisan tinker

# Test pricing calculation
> use App\Services\PricingEngine;
> use App\Models\Experience;
> use Carbon\Carbon;
>
> $pricingEngine = app(PricingEngine::class);
> $retreat = Experience::first();
> $accommodation = $retreat->accommodations()->first();
> $pricing = $pricingEngine->calculateBookingPrice(
    $retreat,
    $accommodation,
    Carbon::now()->addDays(10),
    Carbon::now()->addDays(17),
    2
  );
> print_r($pricing);

# Should output pricing breakdown with discounts and tax
```

---

## 📊 Database Structure

### Key Tables Created:

```
users                           - System users
centers                        - Wellness retreat centers
experiences                    - Retreat definitions
experience_accommodations      - Room configs per retreat
experience_accommodation_prices - Occupancy pricing
experience_schedules          - Day-wise itinerary
experience_recurring          - Recurring patterns
bookings                       - Customer reservations
booking_user_info            - Guest details
reviews                       - Customer feedback
categories                    - Retreat categories
teachers                      - Instructors
amenities                     - Facility features
```

---

## 🔧 Configuration Checklist

- [ ] `.env` configured with database credentials
- [ ] `.env` configured with email settings
- [ ] `.env` configured with Redis (if using)
- [ ] `config/database.php` updated for MySQL
- [ ] `config/app.php` updated with timezone
- [ ] Storage linked: `php artisan storage:link`
- [ ] Queue configured (if using background jobs)
- [ ] Roles created in database
- [ ] Test user created
- [ ] Migrations run successfully

---

## 📱 Testing API Endpoints

### Pricing Calculation API

```bash
curl -X POST http://localhost:8000/api/pricing/calculate \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "experience_id": 1,
    "accommodation_id": 1,
    "arrival_date": "2025-06-01",
    "departure_date": "2025-06-08",
    "guest_count": 2
  }'
```

### Availability Calendar API

```bash
curl http://localhost:8000/api/availability/calendar?experience_id=1&start_date=2025-06-01&end_date=2025-06-30 \
  -H "Authorization: Bearer {token}"
```

---

## 🛠️ Customization Guide

### Add New Field to Retreat

1. Create migration: `php artisan make:migration add_field_to_experiences`
2. Update migration file
3. Update Experience model (fillable + casts)
4. Update view form
5. Update controller (if needed)
6. Run: `php artisan migrate`

### Add New Service Class

1. Create `app/Services/YourService.php`
2. Add to controller constructor (dependency injection)
3. Register in container if needed

### Add New Retreat Type

1. Update Experience model with new status
2. Add validation in StoreRetreatRequest
3. Add business logic in services
4. Update views

---

## 📈 Performance Optimization

### Enable Query Caching

```php
// In PricingEngine.php
$pricing = Cache::remember("pricing:{$id}:{$date}", 1800, function () {
    return $this->calculateBookingPrice(...);
});
```

### Database Indexing

Indexes are already in migrations. For additional:

```php
// In migration
$table->index(['center_id', 'is_draft']);
$table->index(['arrival_date', 'order_status']);
```

### Asset Optimization

```bash
# Build for production
npm run build

# Minify Laravel code
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🔐 Security Checklist

- [ ] All passwords hashed with bcrypt
- [ ] CSRF protection enabled (Laravel default)
- [ ] Form validation on all inputs
- [ ] Authorization policies implemented
- [ ] Rate limiting configured
- [ ] SQL injection prevented (using Eloquent ORM)
- [ ] XSS protection enabled
- [ ] HTTP headers secured
- [ ] Email verification implemented
- [ ] API token authentication working

---

## 🚢 Deployment Instructions

### Deploy to Production

```bash
# 1. Clone repository
git clone <repo-url>
cd balanceboat-center

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 3. Configure environment
cp .env.production .env
php artisan key:generate

# 4. Database setup
php artisan migrate:fresh --seed --force

# 5. Cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Start services
php artisan queue:work
supervisor (for persistent queue workers)

# 7. Set up SSL/TLS
# Use Let's Encrypt or similar
# Configure in web server (nginx/Apache)

# 8. Set up monitoring
# Use New Relic, DataDog, or similar
# Configure log aggregation
```

### Using Docker

```bash
# Build image
docker-compose build

# Start services
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate:fresh --seed
```

---

## 🐛 Troubleshooting

### Issue: "Class does not exist"
**Solution**: Run `composer dump-autoload`

### Issue: "Column not found"
**Solution**: 
```bash
php artisan migrate:fresh --seed
# Or check migration timestamps
```

### Issue: "Permission denied" on storage
**Solution**: 
```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chown -R www-data:www-data storage/
```

### Issue: Queue jobs not processing
**Solution**:
```bash
php artisan queue:restart
php artisan queue:work --tries=3
```

### Issue: CSRF token mismatch
**Solution**:
1. Clear browser cookies
2. Clear Laravel cache: `php artisan cache:clear`
3. Ensure @csrf in form

---

## 📚 Additional Resources

- **Laravel Docs**: https://laravel.com/docs
- **Eloquent ORM**: https://laravel.com/docs/eloquent
- **Blade Templates**: https://laravel.com/docs/blade
- **Spatie Permissions**: https://spatie.be/docs/laravel-permission
- **Tailwind CSS**: https://tailwindcss.com/docs

---

## 📞 Support

For issues or questions:

1. Check the documentation files included
2. Review the code comments
3. Check Laravel logs: `storage/logs/laravel.log`
4. Use `php artisan tinker` for debugging

---

## ✅ Implementation Checklist

- [ ] Laravel project created
- [ ] All code files copied
- [ ] Models created and tested
- [ ] Migrations run successfully
- [ ] Authentication configured
- [ ] Views created and rendering
- [ ] Controllers working
- [ ] Services functional
- [ ] Database populated
- [ ] API endpoints tested
- [ ] Queue configured
- [ ] Caching enabled
- [ ] Security measures in place
- [ ] Tests passing
- [ ] Documentation reviewed
- [ ] Ready for deployment

---

## 🎉 Congratulations!

You now have a **production-ready wellness retreat management platform**. 

The platform includes:
✅ Complete retreat management (CRUD)
✅ Dynamic pricing engine
✅ Availability calendar
✅ Booking system
✅ Multi-tenant architecture
✅ Authentication & authorization
✅ Beautiful dashboard UI
✅ API endpoints
✅ Queue system for async jobs
✅ Test coverage ready

Start building and customizing for your business needs!
