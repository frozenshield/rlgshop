# E-Commerce Backend API Documentation

This is the comprehensive backend API for the E-Commerce platform, built with [Laravel](https://laravel.com). It serves as the core engine powering catalog management, complex order lifecycles, customer profiles (CRM), staff Role-Based Access Control (RBAC), and AI-integrated features (image analysis & conversational chatbots).

## Key Features & App Processes

The backend operates via a set of well-defined RESTful endpoints that trigger specific workflows:
- **Authentication & RBAC**: Dual-authentication strategy. Customers authenticate via Laravel Sanctum tokens or Socialite (Google). Staff authenticate via a separate, highly restrictive path (`AuthController::login`) evaluated against custom Access Matrices (`AccessMatrixService`) which return allowed paths, modules, and granular permissions per role.
- **Product & Catalog Management**: A deeply structured catalog where products (`ProductController`) sit within Categories/Subcategories and are tagged with Brands, Conditions, and distinct attributes (like `PokemonSet` tracking for TCG items). The `ProductService` orchestrates these associations.
- **Order Lifecycle (Fulfillment)**: Orders transition through distinct states (Pending -> Processing -> Shipped -> Delivered) managed by the `CustomerOrderService`. This includes generating packing slips, attaching tracking info (`RefShippingCarrier`), and orchestrating refunds.
- **Customer CRM**: Centralized management of users (`CustomerProfileController`) containing addresses and preferred settings, linked to actionable segments (Segment Ranks).
- **Communication & Reviews**: Bidirectional communication. Customers submit reviews and support messages; staff can reply, update statuses (Pending -> Resolved), and manage public feedback (`CustomerMessageService`, `CustomerReviewService`).
- **Marketing Engine**: Dynamic validation of promo codes based on cart totals and validity dates (`PromoCodeService`).
- **AI Integration**:
    - **Image Analysis**: Uses `GeminiProductAnalyzer` to parse uploaded product images and extract/suggest structured metadata (Name, Brand, Category, Condition).
    - **Conversational Chatbot**: The `StorefrontChatbotService` provides a RAG-style conversational agent using Gemini or a local fallback, dynamically pulling current catalog stock, promos, and Pokemon Set data into the conversation context to assist users with stock inquiries or shipping policies.

---

## Architecture Reference

Below is an index of the primary classes responsible for handling HTTP requests, data validation, and core business logic.

### Controllers (`app/Http/Controllers/Api/`)
Controllers handle the HTTP layer, injecting Services to perform actions and returning JSON responses.

- **`AccessMatrixController`**
  - `index()` - List RBAC matrix configurations.
  - `getStaffModules()` - Retrieve allowed modules for a specific staff member.
  - `update()` - Modify role permissions.
- **`AiChatbotController`**
  - `chat()` - Process conversational prompts and return AI responses.
  - `quickPrompts()` - Return pre-configured quick prompt questions.
- **`AiProductController`**
  - `analyzeImage()` - Extract product metadata from an uploaded image via Gemini.
- **`CustomerMessageController`**
  - `index()`, `store()`, `reply()`, `updateStatus()` - Manage inbox communications.
- **`CustomerOrderController`**
  - `index()`, `store()`, `show()`, `updateStatus()`, `attachFulfillment()`, `processRefund()`, `markPackingSlipPrinted()` - Comprehensive order processing.
- **`CustomerProfileController`**
  - `index()`, `store()`, `show()`, `update()`, `destroy()`, `updateSegmentRank()`, `getCurrentProfile()`, `updateCurrentProfile()`, `getCurrentSettings()`, `updateCurrentSettings()` - User CRM and preferences.
- **`CustomerReviewController`**
  - `index()`, `store()`, `reply()`, `destroy()` - Manage product reviews.
- **`PokemonSetController`**
  - `index()`, `show()`, `series()` - Query TCG-specific metadata tables.
- **`ProductController`**
  - `index()`, `store()`, `show()`, `update()`, `checkDuplicate()`, `destroy()` - Catalog management.
- **`PromoCodeController`**
  - `index()`, `store()`, `show()`, `update()`, `toggle()`, `destroy()`, `validateCode()` - Discount code workflows.
- **`StaffController`**
  - `index()`, `store()`, `show()`, `update()`, `destroy()` - Manage staff roster.

### Auth Controllers (`app/Http/Controllers/Auth/`)
- **`AuthController`**: `register()`, `login()` - Handles standard/staff logins.
- **`SocialAuthController`**: `redirectToGoogle()`, `handleGoogleCallback()` - OAuth flows.

### Form Requests (`app/Http/Requests/`)
Requests encapsulate validation rules, ensuring data integrity before reaching the controller.
- **AI**: `AiChatRequest`
- **Access Matrix**: `UpdateAccessMatrixRequest`
- **Orders**: `AttachOrderFulfillmentRequest`, `RefundCustomerOrderRequest`, `StoreCustomerOrderRequest`, `UpdateCustomerOrderStatusRequest`
- **Products**: `CheckDuplicateProductRequest`, `StoreProductRequest`, `UpdateProductRequest`
- **Communications**: `ReplyCustomerMessageRequest`, `ReplyCustomerReviewRequest`, `StoreCustomerMessageRequest`, `StoreCustomerReviewRequest`, `UpdateCustomerMessageStatusRequest`
- **CRM**: `StoreCustomerProfileRequest`, `UpdateCurrentProfileRequest`, `UpdateCurrentSettingsRequest`, `UpdateCustomerProfileRequest`, `UpdateSegmentRankRequest`
- **Promo Codes**: `StorePromoCodeRequest`, `UpdatePromoCodeRequest`, `ValidatePromoCodeRequest`
- **Staff**: `StoreStaffRequest`, `UpdateStaffRequest`

### Services (`app/Services/`)
Services contain the heavy business logic, transaction handling, and third-party integrations, keeping controllers thin.

- **`AccessMatrixService`**
  - `getMatrixRules()`, `getStaffModules()`, `updateRule()`
- **`CustomerMessageService`**
  - `getMessages()`, `createMessage()`, `replyToMessage()`, `updateMessageStatus()`
- **`CustomerOrderService`**
  - `getOrders()`, `createOrder()`, `updateStatus()`, `attachFulfillment()`, `processRefund()`, `markPackingSlipPrinted()`
- **`CustomerProfileService`**
  - `getProfiles()`, `createProfile()`, `updateProfile()`, `deleteProfile()`, `updateSegmentRank()`, `getCurrentProfile()`, `updateCurrentProfile()`, `getCurrentSettings()`, `updateCurrentSettings()`
- **`CustomerReviewService`**
  - `getReviews()`, `createReview()`, `replyToReview()`, `deleteReview()`
- **`GeminiProductAnalyzer`**
  - `analyze()`, `processImage()`, `buildCategoriesContext()`, `buildBrandsContext()`
- **`StaffService`**
  - `getStaffMembers()`, `createStaff()`, `updateStaff()`, `deleteStaff()`, `resolveRoleId()`
- **`StorefrontChatbotService`**
  - `reply()`, `generateWithGemini()`, `generateLocalFallback()`, `buildCatalogContext()`, `buildPromoContext()`, `matchProducts()`, `formatProductItem()`, `generateSuggestedActions()`, `buildPokemonSetContext()`, `matchPokemonSets()`

---

## Tech Stack

- **PHP 8.3+**
- **Laravel 11.x**
- **Database**: SQLite (Development) / MySQL / PostgreSQL
- **Authentication**: Laravel Sanctum (Token-based) & Custom Matrix (Staff RBAC)

## Setup & Installation

1. **Install PHP Dependencies**
   ```bash
   composer install
   ```
2. **Environment Configuration**
   Copy `.env.example` to `.env` and generate the key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
3. **Database Migration & Seeding**
   ```bash
   php artisan migrate --seed
   ```
   *(Note: The DatabaseSeeder populates roles, access matrices, modules, catalogs, and test orders.)*

## Running the Application & Tests

Start local development server:
```bash
php artisan serve
```

Execute the comprehensive PHPUnit test suite:
```bash
php artisan test
```

## AI Agent Development Guidelines
Please refer to `AGENTS.md` and `.ai/rules` (if present) for application-specific rules, formatting (`pint --format agent`), and specialized testing instructions (`testing-best-practices` skill).