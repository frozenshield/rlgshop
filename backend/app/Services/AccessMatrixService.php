<?php

namespace App\Services;

use App\Models\AccessMatrix;
use App\Models\RefStaffRole;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Collection;

class AccessMatrixService
{
    public function getMatrixRules(?int $roleId, ?int $moduleId): Collection
    {
        $query = AccessMatrix::with(['role', 'module']);

        if ($roleId) {
            $query->where('role_id', $roleId);
        }

        if ($moduleId) {
            $query->where('module_id', $moduleId);
        }

        return $query->orderBy('role_id')->orderBy('module_id')->get();
    }

    public function getStaffModules(array $filters, ?Staff $staff = null): array
    {
        if (! $staff && ! empty($filters['staff_id'])) {
            $staff = Staff::with('role')->find($filters['staff_id']);
        } elseif (! $staff && ! empty($filters['email'])) {
            $staff = Staff::with('role')->where('email', trim($filters['email']))->first();
        }

        $role = null;
        $roleId = null;

        if ($staff) {
            $role = $staff->role;
            $roleId = $staff->ref_staff_role_id;
        } elseif (! empty($filters['role_id'])) {
            $roleId = (int) $filters['role_id'];
            $role = RefStaffRole::find($roleId);
        } elseif (! empty($filters['role'])) {
            $roleTerm = trim(strtolower($filters['role']));

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

        if (! $role) {
            $role = RefStaffRole::find($roleId) ?? RefStaffRole::where('name', 'Admin')->first() ?? RefStaffRole::first();
            $roleId = $role?->id;
        }

        $includeAll = ! empty($filters['include_all']) || ! empty($filters['include_forbidden']);

        $query = AccessMatrix::with('module')->where('role_id', $roleId);

        if (! $includeAll) {
            $query->where('can_read', true);
        }

        $matrixEntries = $query->orderBy('module_id')->get();

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

        $allowedPaths = $modules->where('can_read', true)->pluck('path')->filter()->values()->all();
        $allowedCodes = $modules->where('can_read', true)->pluck('code')->filter()->values()->all();

        return [
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
        ];
    }

    public function updateRule(AccessMatrix $accessMatrix, array $data): AccessMatrix
    {
        $accessMatrix->update($data);
        $accessMatrix->load(['role', 'module']);

        return $accessMatrix;
    }
}
