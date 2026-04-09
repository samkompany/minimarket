# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

### Docker (recommandé)

```bash
# Premier démarrage
cp .env.docker .env
docker compose build
docker compose up -d

# Générer la clé app (une seule fois)
docker compose exec app php artisan key:generate

# Logs en temps réel
docker compose logs -f app

# Lancer un artisan dans le conteneur
docker compose exec app php artisan <commande>

# Arrêter
docker compose down
```

### Développement local (sans Docker)

```bash
composer run dev      # Run all services concurrently (Laravel server, queue, logs, Vite)
npm run dev           # Vite dev server only
php artisan serve     # Laravel server only
```

### Setup local

```bash
composer run setup    # Full setup: install deps, generate key, run migrations
npm install && npm run build
```

### Testing
```bash
composer run test     # Run all PHPUnit tests
php artisan test --filter TestName   # Run a single test
```

### Code Style
```bash
./vendor/bin/pint     # Fix PHP code style (Laravel Pint)
```

### Database
```bash
php artisan migrate          # Run pending migrations
php artisan db:seed          # Seed database
php artisan tinker           # Interactive REPL
```

### Build
```bash
npm run build         # Production build (Vite compiles CSS/JS)
```

## Architecture

### Stack
- **Laravel 12** + **Livewire 3** (Volt for auth single-file components)
- **Tailwind CSS** + **Flowbite** component library
- **SQLite** by default (configurable to MySQL/PostgreSQL via `.env`)
- **Maatwebsite Excel** for import/export, **DomPDF** for PDF invoices

### Key Patterns

**Livewire-first UI**: Almost no traditional controllers. Each page/feature is a Livewire component class in `app/Livewire/` with a paired Blade view in `resources/views/livewire/`. Business logic lives in the component class, not controllers.

**Auth routes** use Livewire Volt (single-file components) via `routes/auth.php`. All other routes are in `routes/web.php`, protected by `auth` + `EnsureUserIsActive` middleware.

**Role-based access**: Three roles on the `users` table (`admin`, `vendeur`, `vendeur_simple`). Check via `User::isAdmin()`, `hasRole()`. Users can be suspended (`suspended_at`, `suspension_reason`).

**Dynamic app config**: `AppSetting` model stores runtime config (app name, logo). Accessed via `AppSetting::get('key', 'default')`. Used across the layout to render dynamic branding.

**Stock system**: Stock is tracked in the `stocks` table (separate from `products`). Movements are recorded in `stock_movements`. The `stock_outs` table records deductions. Products have `min_stock` and `reorder_qty` for alerts.

**Sales flow**: `Sale` (header) → `SaleItems` (lines). Status is `paid` or `draft`. Invoice PDF generated via `InvoiceController` using DomPDF.

**Excel import**: `app/Livewire/Products/` handles product import with barcode/SKU matching logic. Export uses `app/Exports/SalesReportExport.php`.

**Queue**: Database-backed (`QUEUE_CONNECTION=database`). Run with `php artisan queue:listen` (included in `composer run dev`).

### Directory Map
- `app/Models/` — Eloquent models (User, Product, Sale, Purchase, Stock, Category, Expense, Supplier, Invoice, AppSetting, …)
- `app/Livewire/` — All interactive UI components (Dashboard, Products, Sales, Purchases, Stocks, Reports, Expenses, Users, Forms)
- `app/Http/Controllers/` — Minimal: only `InvoiceController` and auth email verification
- `app/Http/Middleware/EnsureUserIsActive.php` — Blocks suspended users
- `resources/views/layouts/` — Main app layout (`app.blade.php`) and guest layout
- `resources/views/livewire/` — Blade templates for Livewire components
- `resources/views/invoices/` — PDF/receipt templates rendered by DomPDF
- `database/migrations/` — 30+ migrations; schema is the source of truth for data structure
