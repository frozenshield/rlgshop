<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefStaffRole extends Model
{
    use HasFactory;

    protected $table = 'ref_staff_role';

    protected $fillable = [
        'name',
        'label',
        'permissions_label',
        'description',
    ];

    /**
     * Get all access matrix rules for this staff role.
     */
    public function accessMatrices(): HasMany
    {
        return $this->hasMany(AccessMatrix::class, 'role_id');
    }

    /**
     * Get all accessible modules for this staff role.
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(RefModule::class, 'access_matrix', 'role_id', 'module_id')
            ->withPivot(['sub_module_id', 'can_read', 'can_create', 'can_update', 'can_delete'])
            ->withTimestamps();
    }
}
