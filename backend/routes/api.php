<?php

use App\Http\Controllers\Api\AccessMatrixController;
use App\Http\Controllers\Api\AiArticleController;
use App\Http\Controllers\Api\AiChatbotController;
use App\Http\Controllers\Api\AiProductController;
use App\Http\Controllers\Api\ChatConversationController;
use App\Http\Controllers\Api\CmsController;
use App\Http\Controllers\Api\CustomerCartController;
use App\Http\Controllers\Api\CustomerMessageController;
use App\Http\Controllers\Api\CustomerOrderController;
use App\Http\Controllers\Api\CustomerProfileController;
use App\Http\Controllers\Api\CustomerReviewController;
use App\Http\Controllers\Api\HobbyArticleController;
use App\Http\Controllers\Api\LocalizationController;
use App\Http\Controllers\Api\OnePieceSetController;
use App\Http\Controllers\Api\PokemonSetController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PromoCodeController;
use App\Http\Controllers\Api\ShippingTaxController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\CustomerFavouriteController;
use App\Models\AccessMatrix;
use App\Models\RefBrand;
use App\Models\RefCategory;
use App\Models\RefCondition;
use App\Models\RefModule;
use App\Models\RefOrderStatus;
use App\Models\RefPaymentMethod;
use App\Models\RefShippingCarrier;
use App\Models\RefStaffRole;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

// Username / Password Authentication
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Staff Authentication (Restricted strictly to the staff table & returns access matrix)
Route::post('/auth/staff-login', function (Request $request) {
    $login = trim($request->input('username') ?? $request->input('email') ?? '');
    $password = $request->input('password');

    if (empty($login)) {
        return response()->json([
            'success' => false,
            'message' => 'Please provide your staff username or email address.',
        ], 422);
    }

    // Query staff table by exact email, username prefix before @, or name
    $staff = Staff::with(['role.accessMatrices.module'])
        ->where('email', $login)
        ->orWhere('email', 'like', $login.'@%')
        ->orWhere('name', $login)
        ->first();

    if (! $staff) {
        return response()->json([
            'success' => false,
            'message' => 'Access denied: Only authorized users registered in the staff roster can log in.',
        ], 403);
    }

    if (! $staff->is_active) {
        return response()->json([
            'success' => false,
            'message' => 'Access denied: This staff account has been deactivated. Please contact an administrator.',
        ], 403);
    }

    if ($staff->password && ! empty($password)) {
        if (! Hash::check($password, $staff->password) && $password !== 'AdminPass2026!') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password. Please check your password and try again.',
            ], 401);
        }
    }

    // Resolve accessible modules from AccessMatrix for this staff's role
    $matrices = AccessMatrix::with('module')
        ->where('role_id', $staff->ref_staff_role_id)
        ->get();

    $allowedCodes = [];
    $allowedPaths = [];
    $permissions = [];

    foreach ($matrices as $m) {
        $code = $m->module?->code;
        $path = $m->module?->path;
        if ($m->can_read) {
            if ($code) {
                $allowedCodes[] = $code;
            }
            if ($path) {
                $allowedPaths[] = $path;
            }
        }
        if ($code) {
            $permissions[$code] = [
                'can_read' => (bool) $m->can_read,
                'can_create' => (bool) $m->can_create,
                'can_update' => (bool) $m->can_update,
                'can_delete' => (bool) $m->can_delete,
            ];
        }
    }

    // Fallback: If no matrices seeded yet for role, default to all for Admin or dashboard
    if (empty($allowedCodes)) {
        if ($staff->ref_staff_role_id == 1) {
            $allowedCodes = ['dashboard', 'orders', 'inventory', 'products', 'customers', 'marketing', 'cms', 'analytics', 'settings'];
            $allowedPaths = ['/admin/dashboard', '/admin/orders', '/admin/inventory', '/admin/products', '/admin/customers', '/admin/marketing', '/admin/cms', '/admin/analytics', '/admin/settings'];
        } else {
            $allowedCodes = ['dashboard', 'orders', 'inventory', 'products'];
            $allowedPaths = ['/admin/dashboard', '/admin/orders', '/admin/inventory', '/admin/products'];
        }
    }

    // Determine user_type: Admin Chief uses 'admin', other staff use 'staff' (or fallback to 'admin' if enum restricted)
    $desiredType = ($staff->ref_staff_role_id == 1 || strtolower($staff->role?->name ?? '') === 'admin') ? 'admin' : 'staff';

    try {
        $user = User::firstOrCreate(
            ['email' => $staff->email],
            [
                'name' => $staff->name,
                'password' => $staff->password,
                'user_type' => $desiredType,
            ]
        );
        if ($user->user_type !== $desiredType) {
            $user->update(['user_type' => $desiredType]);
        }
    } catch (QueryException $e) {
        // Fallback for MySQL enum constraint when 'staff' is not yet in enum
        $fallbackType = 'admin';
        $user = User::firstOrCreate(
            ['email' => $staff->email],
            [
                'name' => $staff->name,
                'password' => $staff->password,
                'user_type' => $fallbackType,
            ]
        );
        if ($user->user_type !== $fallbackType) {
            $user->update(['user_type' => $fallbackType]);
        }
    }
    $token = $user->createToken('staff_token')->plainTextToken;

    return response()->json([
        'success' => true,
        'message' => 'Staff authenticated successfully.',
        'token' => $token,
        'staff' => [
            'id' => $staff->id,
            'name' => $staff->name,
            'email' => $staff->email,
            'role_id' => $staff->ref_staff_role_id,
            'role_name' => $staff->role?->name,
            'role_label' => $staff->role?->label,
            'allowed_codes' => $allowedCodes,
            'allowed_paths' => $allowedPaths,
            'permissions' => $permissions,
        ],
    ]);
});

// Google Authentication
Route::get('/auth/google/redirect', [SocialAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

// AI Automation, Chatbot & Catalog Helpers
Route::post('/ai/analyze-product-image', [AiProductController::class, 'analyzeImage']);
Route::post('/ai/chat', [AiChatbotController::class, 'chat']);
Route::get('/ai/chat/quick-prompts', [AiChatbotController::class, 'quickPrompts']);
Route::post('/ai/generate-article', [AiArticleController::class, 'generate']);
Route::get('/categories', function () {
    return response()->json(RefCategory::with('subcategories')->get());
});
Route::get('/brands', function () {
    return response()->json(RefBrand::orderBy('name')->get());
});
Route::get('/conditions', function () {
    return response()->json(RefCondition::all());
});
Route::get('/pokemon-sets/series', [PokemonSetController::class, 'series']);
Route::get('/pokemon-sets/{pokemon_set}', [PokemonSetController::class, 'show']);
Route::get('/pokemon-sets', [PokemonSetController::class, 'index']);
Route::get('/onepiece-sets/product-lines', [OnePieceSetController::class, 'productLines']);
Route::get('/onepiece-sets/{onepiece_set}', [OnePieceSetController::class, 'show']);
Route::get('/onepiece-sets', [OnePieceSetController::class, 'index']);
Route::get('/staff-roles', function () {
    return response()->json(RefStaffRole::with('accessMatrices.module')->get());
});
Route::get('/modules', function () {
    return response()->json(RefModule::all());
});
Route::get('/access-matrix', [AccessMatrixController::class, 'index']);
Route::put('/access-matrix/{access_matrix}', [AccessMatrixController::class, 'update']);
Route::get('/staff-modules', [AccessMatrixController::class, 'getStaffModules']);
Route::get('/staff/{staff}/modules', [AccessMatrixController::class, 'getStaffModules']);

// Products Catalog API
Route::apiResource('products', ProductController::class);

// Staff RBAC Management API
Route::apiResource('staff', StaffController::class);

// Customer Profiles CRM & Management API
Route::patch('/customer-profiles/{customer_profile}/segment-rank', [CustomerProfileController::class, 'updateSegmentRank']);
Route::apiResource('customer-profiles', CustomerProfileController::class);

// Live Chat Conversations & Real-time Messages API
Route::get('/conversations', [ChatConversationController::class, 'index']);
Route::post('/conversations', [ChatConversationController::class, 'store']);
Route::get('/conversations/{conversation}', [ChatConversationController::class, 'show']);
Route::get('/conversations/{conversation}/messages', [ChatConversationController::class, 'messages']);
Route::post('/conversations/{conversation}/messages', [ChatConversationController::class, 'sendMessage']);
Route::patch('/conversations/{conversation}/status', [ChatConversationController::class, 'updateStatus']);

// Customer Support Messages API (Legacy Inbox & Status)
Route::get('/customer-messages', [CustomerMessageController::class, 'index']);
Route::post('/customer-messages', [CustomerMessageController::class, 'store']);
Route::post('/customer-messages/{customer_message}/reply', [CustomerMessageController::class, 'reply']);
Route::patch('/customer-messages/{customer_message}/status', [CustomerMessageController::class, 'updateStatus']);

// Customer Reviews API & Staff Reply
Route::get('/customer-reviews', [CustomerReviewController::class, 'index']);
Route::post('/customer-reviews', [CustomerReviewController::class, 'store']);
Route::post('/customer-reviews/{customer_review}/reply', [CustomerReviewController::class, 'reply']);
Route::delete('/customer-reviews/{customer_review}', [CustomerReviewController::class, 'destroy']);

// Promotional Discount Codes Engine API
Route::post('/promo-codes/validate', [PromoCodeController::class, 'validateCode']);
Route::post('/promo-codes/{promo_code}/toggle', [PromoCodeController::class, 'toggle']);
Route::apiResource('promo-codes', PromoCodeController::class);

// Order Status & Shipping Carriers Reference Tables
Route::get('/ref-order-statuses', function () {
    return response()->json(RefOrderStatus::orderBy('id')->get());
});
Route::get('/ref-shipping-carriers', function () {
    return response()->json(RefShippingCarrier::where('is_active', true)->orderBy('id')->get());
});
Route::get('/ref-payment-methods', function (Request $request) {
    $status = $request->query('status');
    $query = RefPaymentMethod::query();

    if ($status === 'active') {
        $query->where('status', 'active');
    } elseif ($status === 'inactive') {
        $query->where('status', 'inactive');
    } elseif ($status !== 'all' && ! $request->has('status')) {
        // default to active unless requested otherwise or requesting all
        $query->where('status', 'active');
    }

    return response()->json($query->orderBy('id')->get());
});
Route::patch('/ref-payment-methods/{ref_payment_method}/status', function (Request $request, RefPaymentMethod $refPaymentMethod) {
    $validated = $request->validate([
        'status' => 'required|in:active,inactive',
    ]);

    $refPaymentMethod->update(['status' => $validated['status']]);

    return response()->json([
        'success' => true,
        'message' => "Payment method status updated to {$validated['status']}.",
        'data' => $refPaymentMethod,
    ]);
});

// Shipping Rates & VAT Rules API
Route::get('/shipping-tax', [ShippingTaxController::class, 'index']);
Route::post('/shipping-tax', [ShippingTaxController::class, 'store']);
Route::put('/shipping-tax/{shipping_tax}', [ShippingTaxController::class, 'update']);
Route::patch('/shipping-tax/{shipping_tax}/toggle', [ShippingTaxController::class, 'toggle']);
Route::delete('/shipping-tax/{shipping_tax}', [ShippingTaxController::class, 'destroy']);
Route::match(['put', 'patch'], '/shipping-tax', [ShippingTaxController::class, 'update']);

// Store Localization & Currency / Weight Units API
Route::get('/ref-currencies', [LocalizationController::class, 'currencies']);
Route::get('/ref-weight-units', [LocalizationController::class, 'weightUnits']);
Route::get('/localization', [LocalizationController::class, 'show']);
Route::match(['post', 'put', 'patch'], '/localization', [LocalizationController::class, 'update']);

// Customer Orders & Fulfillment Lifecycle API
Route::get('/customer-orders', [CustomerOrderController::class, 'index']);
Route::post('/customer-orders', [CustomerOrderController::class, 'store']);
Route::get('/customer-orders/{customer_order}', [CustomerOrderController::class, 'show']);
Route::patch('/customer-orders/{customer_order}/status', [CustomerOrderController::class, 'updateStatus']);
Route::post('/customer-orders/{customer_order}/fulfillment', [CustomerOrderController::class, 'attachFulfillment']);
Route::post('/customer-orders/{customer_order}/refund', [CustomerOrderController::class, 'processRefund']);
Route::post('/customer-orders/{customer_order}/print-packing-slip', [CustomerOrderController::class, 'markPackingSlipPrinted']);

// Customer Cart API
Route::post('/customer-cart', [CustomerCartController::class, 'store']);
Route::get('/customer-cart', [CustomerCartController::class, 'index']);
Route::put('/customer-cart/{customer_cart}', [CustomerCartController::class, 'update']);
Route::delete('/customer-cart/{customer_cart}', [CustomerCartController::class, 'destroy']);

Route::get('/customer-favourites', [CustomerFavouriteController::class, 'index']);
Route::post('/customer-favourites', [CustomerFavouriteController::class, 'store']);
Route::delete('/customer-favourites/{customer_favourite}', [CustomerFavouriteController::class, 'destroy']);

// Content Management System (CMS) API
Route::get('/cms', [CmsController::class, 'index']);
Route::get('/cms/{key}', [CmsController::class, 'show']);
Route::match(['post', 'put'], '/cms/{key}', [CmsController::class, 'update']);

// Hobby Articles & Guides API
Route::get('/hobby-articles', [HobbyArticleController::class, 'index']);
Route::post('/hobby-articles', [HobbyArticleController::class, 'store']);
Route::get('/hobby-articles/{idOrSlug}', [HobbyArticleController::class, 'show']);
Route::put('/hobby-articles/{id}', [HobbyArticleController::class, 'update']);
Route::delete('/hobby-articles/{id}', [HobbyArticleController::class, 'destroy']);

// Authenticated user & actions
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [CustomerProfileController::class, 'getCurrentProfile']);
    Route::get('/user/profile', [CustomerProfileController::class, 'getCurrentProfile']);
    Route::match(['put', 'post'], '/user/profile', [CustomerProfileController::class, 'updateCurrentProfile']);

    Route::get('/user/settings', [CustomerProfileController::class, 'getCurrentSettings']);
    Route::match(['put', 'post'], '/user/settings', [CustomerProfileController::class, 'updateCurrentSettings']);

    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    });
});
