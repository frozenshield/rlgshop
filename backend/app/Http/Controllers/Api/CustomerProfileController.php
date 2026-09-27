<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerProfileRequest;
use App\Http\Requests\UpdateCurrentProfileRequest;
use App\Http\Requests\UpdateCurrentSettingsRequest;
use App\Http\Requests\UpdateCustomerProfileRequest;
use App\Http\Requests\UpdateSegmentRankRequest;
use App\Models\CustomerProfile;
use App\Services\CustomerProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerProfileController extends Controller
{
    public function __construct(
        protected CustomerProfileService $customerProfileService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $profiles = $this->customerProfileService->getProfiles($request->all());

        return response()->json([
            'success' => true,
            'count' => $profiles->count(),
            'data' => $profiles,
        ]);
    }

    public function store(StoreCustomerProfileRequest $request): JsonResponse
    {
        $profile = $this->customerProfileService->createProfile($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Customer profile saved successfully.',
            'data' => $profile,
        ], 201);
    }

    public function show(CustomerProfile $customerProfile): JsonResponse
    {
        $customerProfile->load('user');

        return response()->json([
            'success' => true,
            'data' => $customerProfile,
        ]);
    }

    public function update(UpdateCustomerProfileRequest $request, CustomerProfile $customerProfile): JsonResponse
    {
        $profile = $this->customerProfileService->updateProfile($customerProfile, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Customer profile updated successfully.',
            'data' => $profile,
        ]);
    }

    public function destroy(CustomerProfile $customerProfile): JsonResponse
    {
        $this->customerProfileService->deleteProfile($customerProfile);

        return response()->json([
            'success' => true,
            'message' => 'Customer profile deleted successfully.',
        ]);
    }

    public function updateSegmentRank(UpdateSegmentRankRequest $request, CustomerProfile $customerProfile): JsonResponse
    {
        $profile = $this->customerProfileService->updateSegmentRank($customerProfile, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Customer segment rank updated successfully.',
            'data' => $profile,
        ]);
    }

    public function getCurrentProfile(Request $request): JsonResponse
    {
        $merged = $this->customerProfileService->getCurrentProfile($request->user());

        return response()->json($merged);
    }

    public function updateCurrentProfile(UpdateCurrentProfileRequest $request): JsonResponse
    {
        $merged = $this->customerProfileService->updateCurrentProfile($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => $merged,
        ]);
    }

    public function getCurrentSettings(Request $request): JsonResponse
    {
        $settings = $this->customerProfileService->getCurrentSettings($request->user());

        return response()->json($settings);
    }

    public function updateCurrentSettings(UpdateCurrentSettingsRequest $request): JsonResponse
    {
        $settings = $this->customerProfileService->updateCurrentSettings($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully.',
            'data' => $settings,
        ]);
    }
}
