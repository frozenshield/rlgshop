<?php

namespace App\Services;

use App\Models\RefStaffRole;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Collection;

class StaffService
{
    public function getStaffMembers(array $filters): Collection
    {
        $query = Staff::with(['role.accessMatrices.module']);

        if (! empty($filters['role_id'])) {
            $query->where('ref_staff_role_id', (int) $filters['role_id']);
        }

        if (! empty($filters['search'])) {
            $term = trim($filters['search']);
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        return $query->orderBy('id', 'asc')->get();
    }

    public function createStaff(array $data): Staff
    {
        $roleId = $this->resolveRoleId($data);

        $staff = Staff::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => ! empty($data['password']) ? $data['password'] : 'AdminPass2026!',
            'ref_staff_role_id' => $roleId,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return $staff->load(['role.accessMatrices.module']);
    }

    public function updateStaff(Staff $staff, array $data): Staff
    {
        $roleId = $this->resolveRoleId($data, false);

        $updateData = [];
        if (! empty($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (! empty($data['email'])) {
            $updateData['email'] = $data['email'];
        }
        if (! empty($data['password'])) {
            $updateData['password'] = $data['password'];
        }
        if ($roleId) {
            $updateData['ref_staff_role_id'] = $roleId;
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'];
        }

        $staff->update($updateData);

        return $staff->load(['role.accessMatrices.module']);
    }

    public function deleteStaff(Staff $staff): void
    {
        $staff->delete();
    }

    protected function resolveRoleId(array $data, bool $useDefault = true): ?int
    {
        $roleId = $data['ref_staff_role_id']
            ?? $data['staff_role_id']
            ?? $data['role_id']
            ?? null;

        if (! $roleId && ! empty($data['role'])) {
            $roleTerm = trim($data['role']);
            $matchedRole = RefStaffRole::where('name', $roleTerm)
                ->orWhere('label', 'like', "{$roleTerm}%")
                ->orWhere('name', 'like', "%{$roleTerm}%")
                ->first();

            if (! $matchedRole && str_contains(strtolower($roleTerm), 'admin')) {
                $matchedRole = RefStaffRole::where('name', 'Admin')->first();
            }

            $roleId = $matchedRole?->id;
        }

        if (! $roleId && $useDefault) {
            $defaultRole = RefStaffRole::where('name', 'Fulfillment Staff')->first();
            $roleId = $defaultRole?->id ?? 3;
        }

        return $roleId;
    }
}
