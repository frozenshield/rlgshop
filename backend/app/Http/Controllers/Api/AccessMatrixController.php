<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccessMatrix;
use App\Models\RefStaffRole;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccessMatrixController extends Controller
{
    /**
     * Display a listing of access matrix rules.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AccessMatrix::with(['role', 'module']);

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->integer('role_id'));
        }

        if ($request->filled('module_id')) {
            $query->where('module_id', $request->integer('module_id'));
        }

        $records = $query->orderBy('role_id')->orderBy('module_id')->get();

        return response()->json([
            'success' => true,
            'count' => $records->count(),
            'data' => $records,
        ]);
    }

    /**
     * Get accessible modules for a specific staff member or staff role.
     * Used for sidebar filtering and route access verification.
     */
    public function getStaffModules(Request $request, ?Staff $staff = null): JsonResponse
    {
        // 1. Resolve staff if not provided by route model binding
        if (! $staff && $request->filled('staff_id')) {
            $staff = Staff::with('role')->find($request->integer('staff_id'));
        } elseif (! $staff && $request->filled('email')) {
            $staff = Staff::with('role')->where('email', trim($request->string('email')))->first();
        }

        // 2. Resolve Role
        $role = null;
        $roleId = null;

        if ($staff) {
            $role = $staff->role;
            $roleId = $staff->ref_staff_role_id;
        } elseif ($request->filled('role_id')) {
            $roleId = $request->integer('role_id');
            $role = RefStaffRole::find($roleId);
        } elseif ($request->filled('role')) {
            $roleTerm = trim(strtolower($request->string('role')));

            if (str_contains($roleTerm, 'super') || str_contains($roleTerm, 'admin')) {
                $role = RefStaffRole::where('name', 'Admin')->first();
            } elseif (str_contains($roleTerm, 'manage')) {
                $role = RefStaffRole::where('name', 'Store Manager')->first();
            } elseif (str_contains($roleTerm, 'fulfill') || str_contains($roleTerm, 'pack')) {
                $role = RefStaffRole::where('name', 'Fulfillment Staff')->first();
            } else {
                $role = RefStaffRole::where('name', 'like', "%{$roleTerm}%")
                    ->orWhere('label', 'like', "%{$roleTerm}%")
                    ->first();
            }

            $roleId = $role?->id;
        }

        // Fallback to Admin role (id 1) if nothing specified
        if (! $role) {
            $role = RefStaffRole::find($roleId) ?? RefStaffRole::where('name', 'Admin')->first() ?? RefStaffRole::first();
            $roleId = $role?->id;
        }

        // 3. Query Access Matrix entries
        $includeAll = $request->boolean('include_all') || $request->boolean('include_forbidden');

        $query = AccessMatrix::with('module')->where('role_id', $roleId);

        if (! $includeAll) {
            $query->where('can_read', true);
        }

        $matrixEntries = $query->orderBy('module_id')->get();

        // 4. Map into frontend-ready module permissions
        $modules = $matrixEntries->map(function (AccessMatrix $entry): array {
            return [
                'id' => $entry->module_id,
                'matrix_id' => $entry->id,
                'code' => $entry->module?->code,
                'name' => $entry->module?->name,
                'path' => $entry->module?->path,
                'description' => $entry->module?->description,
                'can_read' => (bool) $entry->can_read,
                'can_create' => (bool) $entry->can_create,
                'can_update' => (bool) $entry->can_update,
                'can_delete' => (bool) $entry->can_delete,
            ];
        });

        // Compute allowed paths and codes where can_read is true
        $allowedPaths = $modules->where('can_read', true)->pluck('path')->filter()->values()->all();
        $allowedCodes = $modules->where('can_read', true)->pluck('code')->filter()->values()->all();

        return response()->json([
            'success' => true,
            'role' => $role ? [
                'id' => $role->id,
                'name' => $role->name,
                'label' => $role->label,
                'permissions_label' => $role->permissions_label,
            ] : null,
            'staff' => $staff ? [
                'id' => $staff->id,
                'name' => $staff->name,
                'email' => $staff->email,
            ] : null,
            'modules' => $modules->values(),
            'allowed_paths' => $allowedPaths,
            'allowed_codes' => $allowedCodes,
        ]);
    }

    /**
     * Update access matrix rule permissions.
     */
    public function update(Request $request, AccessMatrix $accessMatrix): JsonResponse
    {
        $validated = $request->validate([
            'can_read' => 'nullable|boolean',
            'can_create' => 'nullable|boolean',
            'can_update' => 'nullable|boolean',
            'can_delete' => 'nullable|boolean',
        ]);

        $accessMatrix->update($validated);
        $accessMatrix->load(['role', 'module']);

        return response()->json([
            'success' => true,
            'message' => 'Module access rule updated successfully.',
            'data' => $accessMatrix,
        ]);
    }
}
