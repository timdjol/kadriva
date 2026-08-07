# Laravel Kit

Quick-start kit: **Laravel 13** + **Filament 5** admin panel.

SQLite out of the box — clone, install, open `/admin`.

## Requirements

- PHP 8.3+
- Composer
- Node.js 18+ (optional, for Vite frontend assets)

## Setup

```bash
git clone https://github.com/timdjol/laravel-kit.git
cd laravel-kit

composer install
cp .env.example .env
php artisan key:generate

touch database/database.sqlite
php artisan migrate --seed

# optional frontend assets
npm install && npm run build

php artisan serve
```

Or use the Composer setup script (after clone + `composer install`):

```bash
composer run setup
php artisan db:seed
php artisan serve
```

## Admin panel

| | |
|---|---|
| URL | [http://localhost:8000/admin](http://localhost:8000/admin) |
| Email | `admin@example.com` |
| Password | `password` |

Create another admin:

```bash
php artisan make:filament-user
```

## Stack

| Package | Version |
|---|---|
| Laravel | ^13 |
| Filament | ^5 |
| Livewire | ^4 (via Filament) |
| Database | SQLite (default) |

## Useful commands

```bash
# New Filament resource
php artisan make:filament-resource Post --generate

# Dev servers (app + queue + logs + Vite)
composer run dev
```

## License

MIT
