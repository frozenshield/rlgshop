<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessMatrix extends Model
{
    use HasFactory;

    protected $table = 'access_matrix';

    protected $fillable = [
        'role_id',
        'module_id',
        'sub_module_id',
        'can_read',
        'can_create',
        'can_update',
        'can_delete',
    ];

    protected function casts(): array
    {
        return [
            'role_id' => 'integer',
            'module_id' => 'integer',
            'sub_module_id' => 'integer',
            'can_read' => 'boolean',
            'can_create' => 'boolean',
            'can_update' => 'boolean',
            'can_delete' => 'boolean',
        ];
    }

    /**
     * Get the staff role this permission entry belongs to.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(RefStaffRole::class, 'role_id');
    }

    /**
     * Get the module this permission entry applies to.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(RefModule::class, 'module_id');
    }
}
