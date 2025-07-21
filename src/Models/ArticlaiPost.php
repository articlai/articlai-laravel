<?php

namespace Articlai\Articlai\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class ArticlaiPost extends Model
{
    use HasFactory;

    protected $table = 'blogs';

    protected $fillable = [
        'title',
        'content',
        'excerpt',
        'slug',
        'meta_title',
        'meta_description',
        'focus_keyword',
        'canonical_url',
        'published_at',
        'custom_fields',
        'status',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'custom_fields' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $dates = [
        'published_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Boot the model and set up event listeners
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug) && config('articlai-laravel.content.auto_generate_slug', true)) {
                $post->slug = $post->generateUniqueSlug($post->title);
            }
        });

        static::updating(function ($post) {
            if ($post->isDirty('title') && config('articlai-laravel.content.auto_generate_slug', true)) {
                $post->slug = $post->generateUniqueSlug($post->title);
            }
        });
    }

    /**
     * Generate a unique slug from the title
     */
    public function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->where('id', '!=', $this->id ?? 0)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter published posts
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->where(function ($q) {
                        $q->whereNull('published_at')
                          ->orWhere('published_at', '<=', now());
                    });
    }

    /**
     * Get the URL for this post (can be overridden in implementation)
     */
    public function getUrlAttribute(): string
    {
        $baseUrl = config('app.url');
        return rtrim($baseUrl, '/') . '/blog/' . $this->slug;
    }

    /**
     * Check if the post is published
     */
    public function isPublished(): bool
    {
        return $this->status === 'published' &&
               ($this->published_at === null || $this->published_at <= now());
    }

    /**
     * Set the custom fields attribute
     */
    public function setCustomFieldsAttribute($value)
    {
        $this->attributes['custom_fields'] = is_string($value) ? $value : json_encode($value);
    }

    /**
     * Get the custom fields attribute
     */
    public function getCustomFieldsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}
