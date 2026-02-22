## Setup
```bash
composer require --dev laravel/dusk
php artisan dusk:install
php artisan dusk:chrome-driver
```

## Run Tests
```bash
php artisan dusk
```

## Test Files
- `ConversationTest.php` - Chat page basics
- `ApiKeyTest.php` - Settings page
- `LandingTest.php` - Homepage
- `AuthTest.php` - Login/register
