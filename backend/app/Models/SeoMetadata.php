<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoMetadata extends Model
{
    use HasFactory;

    protected $table = 'seo_metadata';

    protected $fillable = [
        'entity_type',
        'entity_id',
        'page_name',
        'route_path',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'focus_keyword',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image_url',
        'twitter_card',
        'robots',
        'structured_data_json',
        'seo_score',
        'ai_generated',
        'ai_model',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'entity_id' => 'integer',
            'structured_data_json' => 'array',
            'seo_score' => 'integer',
            'ai_generated' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}

