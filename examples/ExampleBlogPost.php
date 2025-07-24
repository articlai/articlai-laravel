<?php

namespace App\Models;

use Articlai\Articlai\Contracts\ArticlaiConnectable;
use Articlai\Articlai\Traits\ArticlaiConnector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Example model showing how to connect your existing blog model to Articlai
 * 
 * This example assumes you have a blog_posts table with the following structure:
 * - id (primary key)
 * - post_title (string)
 * - post_content (text)
 * - post_excerpt (text, nullable)
 * - post_slug (string, unique)
 * - post_status (enum: draft, published, private)
 * - published_date (timestamp, nullable)
 * - seo_title (string, nullable)
 * - seo_description (text, nullable)
 * - featured_image_url (string, nullable)
 * - created_at (timestamp)
 * - updated_at (timestamp)
 */
class ExampleBlogPost extends Model implements ArticlaiConnectable
{
    use HasFactory;
    use ArticlaiConnector;

    protected $table = 'blog_posts';

    protected $fillable = [
        'post_title',
        'post_content',
        'post_excerpt',
        'post_slug',
        'post_status',
        'published_date',
        'seo_title',
        'seo_description',
        'featured_image_url',
    ];

    protected $casts = [
        'published_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Define field mapping between Articlai fields and your model fields
     */
    protected $articlaiFieldMapping = [
        'title' => 'post_title',
        'content' => 'post_content',
        'excerpt' => 'post_excerpt',
        'slug' => 'post_slug',
        'status' => 'post_status',
        'published_at' => 'published_date',
        'meta_title' => 'seo_title',
        'meta_description' => 'seo_description',
        'banner_url' => 'featured_image_url', // For models without media library
    ];

    /**
     * Override the published status logic if needed
     */
    public function isPublished(): bool
    {
        return $this->post_status === 'published' && 
               ($this->published_date === null || $this->published_date <= now());
    }

    /**
     * Override URL generation if needed
     */
    public function getUrl(): string
    {
        return route('blog.show', $this->post_slug);
    }

    /**
     * Custom scope for published posts (optional override)
     */
    public function scopePublished($query)
    {
        return $query->where('post_status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_date')
                    ->orWhere('published_date', '<=', now());
            });
    }

    /**
     * Custom scope for filtering by status (optional override)
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('post_status', $status);
    }

    /**
     * Boot method to handle automatic slug generation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->post_slug)) {
                $post->post_slug = $post->generateUniqueSlug($post->post_title);
            }
        });

        static::updating(function ($post) {
            if ($post->isDirty('post_title') && empty($post->post_slug)) {
                $post->post_slug = $post->generateUniqueSlug($post->post_title);
            }
        });
    }
}
