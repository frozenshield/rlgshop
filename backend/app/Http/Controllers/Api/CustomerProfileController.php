<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomerProfileController extends Controller
{
    /**
     * Display a listing of customer profiles (CRM Admin View).
     */
    public function index(Request $request): JsonResponse
    {
        $query = CustomerProfile::with('user');

        if ($request->filled('segment') && $request->string('segment') !== 'All') {
            $query->where('segment', $request->string('segment'));
        }

        if ($request->filled('search')) {
            $term = $request->string('search');
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('username', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('city', 'like', "%{$term}%")
                    ->orWhereHas('user', function ($uq) use ($term): void {
                        $uq->where('email', 'like', "%{$term}%")
                            ->orWhere('name', 'like', "%{$term}%");
                    });
            });
        }

        $profiles = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'count' => $profiles->count(),
            'data' => $profiles,
        ]);
    }

    /**
     * Store a newly created customer profile.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'avatar' => 'nullable|string',
            'address_line1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'favorite_franchise' => 'nullable|string|max:100',
            'bio' => 'nullable|string',
            'two_factor_auth' => 'nullable|boolean',
            'email_notifications' => 'nullable|boolean',
            'order_updates_sms' => 'nullable|boolean',
            'marketing_emails' => 'nullable|boolean',
            'currency_preference' => 'nullable|string|max:10',
            'public_collection' => 'nullable|boolean',
            'segment' => 'nullable|in:VIP,Regular,Wholesale,Inactive',
            'notes' => 'nullable|string',
        ]);

        $user = User::firstOrCreate(
            ['email' => $validated['email']],
            [
                'name' => $validated['name'] ?? 'Collector',
                'password' => Hash::make('password123'),
                'user_type' => 'customer',
            ]
        );

        $profile = CustomerProfile::updateOrCreate(
            ['user_id' => $user->id],
            array_merge($validated, ['user_id' => $user->id])
        );

        $profile->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Customer profile saved successfully.',
            'data' => $profile,
        ], 201);
    }

    /**
     * Display the specified customer profile.
     */
    public function show(CustomerProfile $customerProfile): JsonResponse
    {
        $customerProfile->load('user');

        return response()->json([
            'success' => true,
            'data' => $customerProfile,
        ]);
    }

    /**
     * Update the specified customer profile.
     */
    public function update(Request $request, CustomerProfile $customerProfile): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'avatar' => 'nullable|string',
            'address_line1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'favorite_franchise' => 'nullable|string|max:100',
            'bio' => 'nullable|string',
            'two_factor_auth' => 'nullable|boolean',
            'email_notifications' => 'nullable|boolean',
            'order_updates_sms' => 'nullable|boolean',
            'marketing_emails' => 'nullable|boolean',
            'currency_preference' => 'nullable|string|max:10',
            'public_collection' => 'nullable|boolean',
            'segment' => 'nullable|in:VIP,Regular,Wholesale,Inactive',
            'notes' => 'nullable|string',
        ]);

        $customerProfile->update($validated);

        if (! empty($validated['name']) && $customerProfile->user) {
            $customerProfile->user->update(['name' => $validated['name']]);
        }

        $customerProfile->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Customer profile updated successfully.',
            'data' => $customerProfile,
        ]);
    }

    /**
     * Remove the specified customer profile.
     */
    public function destroy(CustomerProfile $customerProfile): JsonResponse
    {
        $customerProfile->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer profile deleted successfully.',
        ]);
    }

    /**
     * Get the authenticated user's profile.
     */
    public function getCurrentProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $profile = CustomerProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $user->name,
                'username' => strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $user->name ?? 'collector')),
                'country' => 'Philippines',
                'favorite_franchise' => 'Pokémon TCG',
            ]
        );

        $merged = array_merge($user->toArray(), $profile->toArray(), [
            'email' => $user->email,
            'name' => $profile->name ?: $user->name,
            'avatar' => $profile->avatar ?: $user->avatar,
        ]);

        return response()->json($merged);
    }

    /**
     * Update the authenticated user's profile details & shipping address.
     */
    public function updateCurrentProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'avatar' => 'nullable|string',
            'address_line1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'favorite_franchise' => 'nullable|string|max:100',
            'bio' => 'nullable|string',
        ]);

        $profile = CustomerProfile::firstOrCreate(['user_id' => $user->id]);
        $profile->update($validated);

        if (! empty($validated['name']) || ! empty($validated['avatar'])) {
            $userUpdate = [];
            if (! empty($validated['name'])) {
                $userUpdate['name'] = $validated['name'];
            }
            if (! empty($validated['avatar'])) {
                $userUpdate['avatar'] = $validated['avatar'];
            }
            $user->update($userUpdate);
        }

        $merged = array_merge($user->fresh()->toArray(), $profile->fresh()->toArray(), [
            'email' => $user->email,
            'name' => $profile->name ?: $user->name,
            'avatar' => $profile->avatar ?: $user->avatar,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => $merged,
        ]);
    }

    /**
     * Get the authenticated user's settings / preferences.
     */
    public function getCurrentSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = CustomerProfile::firstOrCreate(['user_id' => $user->id]);

        return response()->json([
            'two_factor_auth' => (bool) $profile->two_factor_auth,
            'email_notifications' => (bool) $profile->email_notifications,
            'order_updates_sms' => (bool) $profile->order_updates_sms,
            'marketing_emails' => (bool) $profile->marketing_emails,
            'currency_preference' => $profile->currency_preference ?: 'PHP',
            'public_collection' => (bool) $profile->public_collection,
        ]);
    }

    /**
     * Update authenticated user's settings, notifications, and security password.
     */
    public function updateCurrentSettings(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'two_factor_auth' => 'nullable|boolean',
            'email_notifications' => 'nullable|boolean',
            'order_updates_sms' => 'nullable|boolean',
            'marketing_emails' => 'nullable|boolean',
            'currency_preference' => 'nullable|string|max:10',
            'public_collection' => 'nullable|boolean',
            'current_password' => 'nullable|string',
            'new_password' => 'nullable|string|min:8|confirmed',
        ]);

        // Handle password change if requested
        if (! empty($validated['new_password'])) {
            if (empty($validated['current_password']) || ! Hash::check($validated['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['The current password provided is incorrect.'],
                ]);
            }

            $user->update([
                'password' => Hash::make($validated['new_password']),
            ]);
        }

        $profile = CustomerProfile::firstOrCreate(['user_id' => $user->id]);
        $profile->update([
            'two_factor_auth' => $validated['two_factor_auth'] ?? $profile->two_factor_auth,
            'email_notifications' => $validated['email_notifications'] ?? $profile->email_notifications,
            'order_updates_sms' => $validated['order_updates_sms'] ?? $profile->order_updates_sms,
            'marketing_emails' => $validated['marketing_emails'] ?? $profile->marketing_emails,
            'currency_preference' => $validated['currency_preference'] ?? $profile->currency_preference,
            'public_collection' => $validated['public_collection'] ?? $profile->public_collection,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully.',
            'data' => [
                'two_factor_auth' => (bool) $profile->two_factor_auth,
                'email_notifications' => (bool) $profile->email_notifications,
                'order_updates_sms' => (bool) $profile->order_updates_sms,
                'marketing_emails' => (bool) $profile->marketing_emails,
                'currency_preference' => $profile->currency_preference,
                'public_collection' => (bool) $profile->public_collection,
            ],
        ]);
    }
}
