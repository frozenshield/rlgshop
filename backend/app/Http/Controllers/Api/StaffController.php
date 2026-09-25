<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RefStaffRole;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    /**
     * Display a listing of staff members.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Staff::with(['role.accessMatrices.module']);

        if ($request->filled('role_id')) {
            $query->where('ref_staff_role_id', $request->integer('role_id'));
        }

        if ($request->filled('search')) {
            $term = $request->string('search');
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        $staff = $query->orderBy('id', 'asc')->get();

        return response()->json([
            'success' => true,
            'count' => $staff->count(),
            'data' => $staff,
        ]);
    }

    /**
     * Store a newly created staff member in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:staff,email',
            'ref_staff_role_id' => 'nullable|integer',
            'staff_role_id' => 'nullable|integer',
            'role_id' => 'nullable|integer',
            'role' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        // Resolve staff role ID flexibly
        $roleId = $validated['ref_staff_role_id']
            ?? $validated['staff_role_id']
            ?? $validated['role_id']
            ?? null;

        if (! $roleId && ! empty($validated['role'])) {
            $roleTerm = trim($validated['role']);
            $matchedRole = RefStaffRole::where('name', $roleTerm)
                ->orWhere('label', 'like', "{$roleTerm}%")
                ->orWhere('name', 'like', "%{$roleTerm}%")
                ->first();

            // Fallback for 'Super Admin' -> 'Admin'
            if (! $matchedRole && str_contains(strtolower($roleTerm), 'admin')) {
                $matchedRole = RefStaffRole::where('name', 'Admin')->first();
            }

            $roleId = $matchedRole?->id;
        }

        // Default to Fulfillment Staff (id 3) if not found
        if (! $roleId) {
            $defaultRole = RefStaffRole::where('name', 'Fulfillment Staff')->first();
            $roleId = $defaultRole?->id ?? 3;
        }

        $staff = Staff::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'ref_staff_role_id' => $roleId,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $staff->load(['role.accessMatrices.module']);

        return response()->json([
            'success' => true,
            'message' => 'Staff member created successfully.',
            'data' => $staff,
        ], 201);
    }

    /**
     * Display the specified staff member.
     */
    public function show(Staff $staff): JsonResponse
    {
        $staff->load(['role.accessMatrices.module']);

        return response()->json([
            'success' => true,
            'data' => $staff,
        ]);
    }

    /**
     * Update the specified staff member in storage.
     */
    public function update(Request $request, Staff $staff): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => "nullable|string|email|max:255|unique:staff,email,{$staff->id}",
            'ref_staff_role_id' => 'nullable|integer',
            'staff_role_id' => 'nullable|integer',
            'role_id' => 'nullable|integer',
            'role' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $roleId = $validated['ref_staff_role_id']
            ?? $validated['staff_role_id']
            ?? $validated['role_id']
            ?? null;

        if (! $roleId && ! empty($validated['role'])) {
            $roleTerm = trim($validated['role']);
            $matchedRole = RefStaffRole::where('name', $roleTerm)
                ->orWhere('label', 'like', "{$roleTerm}%")
                ->first();

            if (! $matchedRole && str_contains(strtolower($roleTerm), 'admin')) {
                $matchedRole = RefStaffRole::where('name', 'Admin')->first();
            }

            $roleId = $matchedRole?->id;
        }

        $updateData = [];
        if (! empty($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }
        if (! empty($validated['email'])) {
            $updateData['email'] = $validated['email'];
        }
        if ($roleId) {
            $updateData['ref_staff_role_id'] = $roleId;
        }
        if (isset($validated['is_active'])) {
            $updateData['is_active'] = $validated['is_active'];
        }

        $staff->update($updateData);
        $staff->load(['role.accessMatrices.module']);

        return response()->json([
            'success' => true,
            'message' => 'Staff member updated successfully.',
            'data' => $staff,
        ]);
    }

    /**
     * Remove the specified staff member from storage.
     */
    public function destroy(Staff $staff): JsonResponse
    {
        $staff->delete();

        return response()->json([
            'success' => true,
            'message' => 'Staff member deleted successfully.',
        ]);
    }
}
