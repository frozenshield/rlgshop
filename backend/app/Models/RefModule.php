<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefModule extends Model
{
    use HasFactory;

    protected $table = 'ref_module';

    protected $fillable = [
        'name',
        'code',
        'path',
        'description',
    ];

    /**
     * Get all access matrix rules for this module.
     */
    public function accessMatrices(): HasMany
    {
        return $this->hasMany(AccessMatrix::class, 'module_id');
    }

    /**
     * Get all roles that have access to this module.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RefStaffRole::class, 'access_matrix', 'module_id', 'role_id')
            ->withPivot(['sub_module_id', 'can_read', 'can_create', 'can_update', 'can_delete'])
            ->withTimestamps();
    }
}
