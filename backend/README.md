# E-Commerce Backend

This is the backend API for the E-Commerce platform, built with [Laravel](https://laravel.com). It powers the catalog, order management, customer profiles, staff RBAC, and AI chatbot integration.

## Key Features

- **Robust REST API**: Built on Laravel with expressive routing and Eloquent ORM.
- **Role-Based Access Control (RBAC)**: Secure authentication for staff (custom matrix) and customers (Laravel Sanctum).
- **Product Catalog Management**: Comprehensive support for products, categories, subcategories, brands, conditions, and special attributes (e.g., Pokémon sets).
- **Order Lifecycle Management**: Handles everything from order creation, status tracking, fulfillment, packing slips to shipping carriers integration.
- **Customer Relationship Management (CRM)**: Manage customer profiles, support messages, reviews, and promotional codes.
- **AI Integration**: Endpoints for AI product image analysis and interactive chatbot functionalities.

## Tech Stack

- **PHP 8.3+**
- **Laravel 11.x**
- **Database**: SQLite (Development) / MySQL / PostgreSQL
- **Authentication**: Laravel Sanctum & Custom Staff Authentication Matrix

## Setup & Installation

1. **Install PHP Dependencies**
   ```bash
   composer install
   ```

2. **Environment Configuration**
   Copy `.env.example` to `.env` and generate the application key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database Migration & Seeding**
   Ensure your database is configured in `.env`, then run the migrations and seeders:
   ```bash
   php artisan migrate --seed
   ```
   *(Note: The `DatabaseSeeder` includes extensive seed data for roles, staff, modules, categories, products, orders, etc.)*

4. **Install Node Dependencies**
   If required for frontend assets/Vite integration:
   ```bash
   npm install
   npm run build
   ```

## Running the Application

To start the local development server:

```bash
php artisan serve
```

For frontend asset compilation during development:

```bash
npm run dev
```

## Running Tests

Execute the PHPUnit test suite via Laravel Artisan:

```bash
php artisan test
```

## Documentation & Guidelines

Please refer to `AGENTS.md` in the project root for code conventions, tools available, and instructions specific to AI code generation within this repository.
