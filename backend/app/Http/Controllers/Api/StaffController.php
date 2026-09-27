<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Models\Staff;
use App\Services\StaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function __construct(
        protected StaffService $staffService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $staff = $this->staffService->getStaffMembers($request->all());

        return response()->json([
            'success' => true,
            'count' => $staff->count(),
            'data' => $staff,
        ]);
    }

    public function store(StoreStaffRequest $request): JsonResponse
    {
        $staff = $this->staffService->createStaff($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Staff member created successfully.',
            'data' => $staff,
        ], 201);
    }

    public function show(Staff $staff): JsonResponse
    {
        $staff->load(['role.accessMatrices.module']);

        return response()->json([
            'success' => true,
            'data' => $staff,
        ]);
    }

    public function update(UpdateStaffRequest $request, Staff $staff): JsonResponse
    {
        $updatedStaff = $this->staffService->updateStaff($staff, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Staff member updated successfully.',
            'data' => $updatedStaff,
        ]);
    }

    public function destroy(Staff $staff): JsonResponse
    {
        $this->staffService->deleteStaff($staff);

        return response()->json([
            'success' => true,
            'message' => 'Staff member deleted successfully.',
        ]);
    }
}
