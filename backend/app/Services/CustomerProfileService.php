<?php

namespace App\Services;

use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomerProfileService
{
    public function getProfiles(array $filters): Collection
    {
        $query = CustomerProfile::with(['user', 'shippingAddress']);

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('email', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['segment'])) {
            $query->where('segment', $filters['segment'])
                ->orWhere('segment_rank', $filters['segment']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function createProfile(array $data): CustomerProfile
    {
        $user = User::firstOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'] ?? 'Collector',
                'password' => Hash::make('password123'),
                'user_type' => 'customer',
            ]
        );

        $profile = CustomerProfile::updateOrCreate(
            ['user_id' => $user->id],
            array_merge($data, ['user_id' => $user->id])
        );

        return $profile->load('user');
    }

    public function updateProfile(CustomerProfile $customerProfile, array $data): CustomerProfile
    {
        $customerProfile->update($data);

        if (! empty($data['name']) && $customerProfile->user) {
            $customerProfile->user->update(['name' => $data['name']]);
        }

        return $customerProfile->load('user');
    }

    public function deleteProfile(CustomerProfile $customerProfile): void
    {
        $customerProfile->delete();
    }

    public function updateSegmentRank(CustomerProfile $customerProfile, array $data): CustomerProfile
    {
        $customerProfile->update([
            'segment_rank' => $data['segment_rank'],
            'segment' => $data['segment_rank'],
            'notes' => $data['notes'] ?? $customerProfile->notes,
        ]);

        return $customerProfile->load(['user', 'shippingAddress']);
    }

    public function getCurrentProfile(User $user): array
    {
        $profile = CustomerProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $user->name,
                'username' => strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $user->name ?? 'collector')),
                'country' => 'Philippines',
                'favorite_franchise' => 'Pokémon TCG',
            ]
        );

        return array_merge($user->toArray(), $profile->toArray(), [
            'email' => $user->email,
            'name' => $profile->name ?: $user->name,
            'avatar' => $profile->avatar ?: $user->avatar,
        ]);
    }

    public function updateCurrentProfile(User $user, array $data): array
    {
        $profile = CustomerProfile::firstOrCreate(['user_id' => $user->id]);
        $profile->update($data);

        if (! empty($data['name']) || ! empty($data['avatar'])) {
            $userUpdate = [];
            if (! empty($data['name'])) {
                $userUpdate['name'] = $data['name'];
            }
            if (! empty($data['avatar'])) {
                $userUpdate['avatar'] = $data['avatar'];
            }
            $user->update($userUpdate);
        }

        return array_merge($user->fresh()->toArray(), $profile->fresh()->toArray(), [
            'email' => $user->email,
            'name' => $profile->name ?: $user->name,
            'avatar' => $profile->avatar ?: $user->avatar,
        ]);
    }

    public function getCurrentSettings(User $user): array
    {
        $profile = CustomerProfile::firstOrCreate(['user_id' => $user->id]);

        return [
            'two_factor_auth' => (bool) $profile->two_factor_auth,
            'email_notifications' => (bool) $profile->email_notifications,
            'order_updates_sms' => (bool) $profile->order_updates_sms,
            'marketing_emails' => (bool) $profile->marketing_emails,
            'currency_preference' => $profile->currency_preference ?: 'PHP',
            'public_collection' => (bool) $profile->public_collection,
        ];
    }

    public function updateCurrentSettings(User $user, array $data): array
    {
        if (! empty($data['new_password'])) {
            if (empty($data['current_password']) || ! Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['The current password provided is incorrect.'],
                ]);
            }

            $user->update([
                'password' => Hash::make($data['new_password']),
            ]);
        }

        $profile = CustomerProfile::firstOrCreate(['user_id' => $user->id]);
        $profile->update([
            'two_factor_auth' => $data['two_factor_auth'] ?? $profile->two_factor_auth,
            'email_notifications' => $data['email_notifications'] ?? $profile->email_notifications,
            'order_updates_sms' => $data['order_updates_sms'] ?? $profile->order_updates_sms,
            'marketing_emails' => $data['marketing_emails'] ?? $profile->marketing_emails,
            'currency_preference' => $data['currency_preference'] ?? $profile->currency_preference,
            'public_collection' => $data['public_collection'] ?? $profile->public_collection,
        ]);

        return [
            'two_factor_auth' => (bool) $profile->two_factor_auth,
            'email_notifications' => (bool) $profile->email_notifications,
            'order_updates_sms' => (bool) $profile->order_updates_sms,
            'marketing_emails' => (bool) $profile->marketing_emails,
            'currency_preference' => $profile->currency_preference,
            'public_collection' => (bool) $profile->public_collection,
        ];
    }
}
