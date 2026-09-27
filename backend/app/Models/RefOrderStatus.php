<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefOrderStatus extends Model
{
    use HasFactory;

    protected $table = 'ref_order_status';

    protected $fillable = [
        'name',
        'label',
        'badge_color',
        'description',
    ];

    /**
     * Orders with this status.
     *
     * @return HasMany<CustomerOrder, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(CustomerOrder::class, 'ref_order_status_id');
    }
}
