# Repository Guidelines

## Project Structure & Module Organization

This is a Laravel 12 application. Backend code lives in `app/`, with domain logic split across `Models`, `Services`, `Policies`, `Observers`, `Imports`, `Exports`, and `ViewModels`. HTTP controllers, middleware, and requests belong under `app/Http`. Routes are in `routes/web.php`, `routes/api.php`, `routes/auth.php`, and `routes/console.php`. Database migrations, factories, and seeders live in `database/`. Frontend assets are in `resources/css` and `resources/js`, with module CSS under `resources/css/modules`. Public assets are in `public/`. Tests are under `tests/Feature`, with shared Pest setup in `tests/Pest.php`.

## Build, Test, and Development Commands

- `composer install`: install PHP dependencies.
- `npm install`: install frontend dependencies.
- `composer run setup`: install dependencies, create `.env`, generate the app key, migrate, and build assets.
- `composer run dev`: run Laravel, the queue listener, and Vite together for local development.
- `npm run dev`: start only the Vite dev server.
- `npm run build`: build production frontend assets with Vite.
- `composer run test` or `php artisan test`: clear config and run the Pest test suite.
- `./vendor/bin/pint`: format PHP code using Laravel Pint.

## Coding Style & Naming Conventions

Follow `.editorconfig`: UTF-8, LF line endings, spaces, 4-space indentation, final newline, and trimmed trailing whitespace. YAML uses 2 spaces. Format PHP with Pint and use PSR-4 namespaces from `composer.json`: `App\` maps to `app/`, `Tests\` to `tests/`. Prefer descriptive class names such as `ProductImport`, `BranchContextTest`, or `CostVisibilityRestrictionTest`. Keep JavaScript modules in `resources/js/components` or feature folders.

## Testing Guidelines

The project uses Pest with Laravel testing helpers. Add feature tests under `tests/Feature`, grouping auth tests in `tests/Feature/Auth` when relevant. Name files after the behavior or module under test, ending with `Test.php`. Run `composer run test` before opening a pull request. Add regression tests for permission, role, branch, sales, expense, and user-management changes.

## Commit & Pull Request Guidelines

Recent commits use Conventional Commit prefixes with scopes, often in Spanish, for example `fix(users): corregir validacion...` and `feat(expenses): permitir...`. Use `feat`, `fix`, `ci`, `test`, or `refactor` with a concise scope. Pull requests should include a short problem summary, the implemented change, test results, linked issue or task when available, and screenshots for visible UI changes.

## Security & Configuration Tips

Do not commit real secrets from `.env`, deploy keys, database dumps, or generated archives. Use `.env.example` for documented configuration. When changing permissions or roles, clear cached permissions where appropriate and verify behavior with the relevant Pest tests.
