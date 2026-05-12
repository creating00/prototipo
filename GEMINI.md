# Gemini Project Context: Prototipo

This project is a comprehensive inventory and sales management system built with Laravel 12. It supports multi-branch operations, product management with branch-specific pricing/stock, provider management, and sales reporting.

## 🚀 Quick Start

- **Full Setup:** `composer setup` (Installs dependencies, runs migrations, builds assets).
- **Development:** `composer dev` (Runs `artisan serve`, queue listener, and Vite concurrently).
- **Testing:** `composer test` (Runs Pest tests).
- **Frontend Build:** `npm run build`.

## 🛠 Technology Stack

- **Backend:** PHP 8.2+, [Laravel 12](https://laravel.com).
- **Frontend:** [AdminLTE 4](https://adminlte.io) (Bootstrap 5), [Tailwind CSS 3/4](https://tailwindcss.com), [Alpine.js](https://alpinejs.dev), [Vite](https://vitejs.dev).
- **Database:** Eloquent ORM (supports MySQL, PostgreSQL, SQLite).
- **Key Packages:**
    - `spatie/laravel-permission`: Role and permission management.
    - `maatwebsite/excel`: Excel exports/imports.
    - `barryvdh/laravel-dompdf`: PDF generation for receipts and tickets.
    - `laravel/sanctum`: API authentication.
    - `pestphp/pest`: Testing framework.

## 🏗 Architecture & Conventions

### Service Layer Pattern
Business logic is strictly encapsulated within `app/Services`. Controllers should remain lean, delegating complex operations to the relevant service.
- **Example:** `ProductService` handles product creation, image processing, and branch-specific synchronization.

### State Management (Enums)
Extensive use of PHP 8 Enums for statuses, types, and labels located in `app/Enums`.
- Always use Enums instead of hardcoded strings or integers for state tracking.

### Multi-Branch Context
Many entities (like Products) have branch-specific data (stock, prices). 
- Use `ProductBranch` and `ProductBranchPrice` to manage these relations.
- The `AuthTrait` and `BranchService` are often used to determine the current branch context.

### Routing
- **Web Routes:** `routes/web.php` (Uses custom helpers `webResource` and `resourceWithExtras` for consistent naming).
- **API Routes:** `routes/api.php` (Supports e-commerce integration and mobile/SPA clients).

### Testing
- Tests are written using **Pest** and located in `tests/Feature` and `tests/Unit`.
- Use `RefreshDatabase` trait for feature tests.

## 📁 Key Directories

- `app/Enums`: PHP Enums for all system statuses and types.
- `app/Services`: Core business logic and domain services.
- `app/Models`: Eloquent models with heavy use of traits (`AuthTrait`, `PriceFormattingTrait`).
- `app/Helpers`: Custom helper functions including routing helpers.
- `resources/views`: Blade templates, often utilizing AdminLTE components.
- `routes`: Application entry points (Web, API, Auth, Console).

## 📝 Development Notes

- **Images:** Managed via `ImageService`. Supports local storage and external URLs.
- **Prices:** Handled with currency awareness (ARS/USD) via `ProductBranchPrice`.
- **Permissions:** Integrated into policies (`app/Policies`) and controllers.
