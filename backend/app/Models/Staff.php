<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staff';

    protected $fillable = [
        'name',
        'email',
        'password',
        'ref_staff_role_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    protected $appends = [
        'role_name',
        'role_label',
        'permissions',
    ];

    protected function casts(): array
    {
        return [
            'ref_staff_role_id' => 'integer',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the staff role.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(RefStaffRole::class, 'ref_staff_role_id');
    }

    /**
     * Compatibility alias getter for staff_role_id / role_id.
     */
    public function getStaffRoleIdAttribute(): ?int
    {
        return $this->attributes['ref_staff_role_id'] ?? null;
    }

    /**
     * Compatibility alias setter for staff_role_id / role_id.
     */
    public function setStaffRoleIdAttribute(?int $value): void
    {
        $this->attributes['ref_staff_role_id'] = $value;
    }

    /**
     * Role name accessor (e.g. "Admin", "Store Manager", "Fulfillment Staff").
     */
    public function getRoleNameAttribute(): ?string
    {
        return $this->role?->name;
    }

    /**
     * Role label accessor (e.g. "Super Admin (Unrestricted)").
     */
    public function getRoleLabelAttribute(): ?string
    {
        return $this->role?->label;
    }

    /**
     * Active permissions list accessor parsed into an array.
     *
     * @return array<int, string>
     */
    public function getPermissionsAttribute(): array
    {
        if (empty($this->role?->permissions_label)) {
            return [];
        }

        return array_map('trim', explode(',', $this->role->permissions_label));
    }
}
