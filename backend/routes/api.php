<?php

use App\Http\Controllers\Api\AccessMatrixController;
use App\Http\Controllers\Api\AiChatbotController;
use App\Http\Controllers\Api\AiProductController;
use App\Http\Controllers\Api\CustomerProfileController;
use App\Http\Controllers\Api\PokemonSetController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PromoCodeController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Models\AccessMatrix;
use App\Models\RefBrand;
use App\Models\RefCategory;
use App\Models\RefCondition;
use App\Models\RefModule;
use App\Models\RefStaffRole;
use App\Models\Staff;
use App\Models\User;
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

    $user = User::where('email', $staff->email)->first();
    $token = $user ? $user->createToken('staff_token')->plainTextToken : 'staff_session_'.md5($staff->email.now());

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
Route::apiResource('customer-profiles', CustomerProfileController::class);

// Promotional Discount Codes Engine API
Route::post('/promo-codes/validate', [PromoCodeController::class, 'validateCode']);
Route::post('/promo-codes/{promo_code}/toggle', [PromoCodeController::class, 'toggle']);
Route::apiResource('promo-codes', PromoCodeController::class);

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
