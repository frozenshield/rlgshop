<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HobbyArticle extends Model
{
    use HasFactory;

    protected $table = 'hobby_articles';

    protected $fillable = [
        'slug',
        'title',
        'category',
        'author',
        'published_at',
        'status',
        'summary',
        'content',
        'image_url',
        'sources',
        'views_count',
        'is_featured',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'published_at' => 'date',
        'sources' => 'array',
        'is_featured' => 'boolean',
        'views_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (HobbyArticle $article): void {
            if (empty($article->slug) && ! empty($article->title)) {
                $baseSlug = Str::slug($article->title);
                $slug = $baseSlug;
                $counter = 1;

                while (static::where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}-{$counter}";
                    $counter++;
                }

                $article->slug = $slug;
            }

            if (empty($article->published_at)) {
                $article->published_at = now()->toDateString();
            }
        });
    }

    /**
     * Scope to filter published articles.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'Published');
    }

    /**
     * Scope to filter featured articles.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }
}
