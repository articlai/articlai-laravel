<?php

namespace Articlai\Articlai\Models;

use Articlai\Articlai\Contracts\ArticlaiConnectable;
use Articlai\Articlai\Traits\ArticlaiConnector;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ArticlaiPost extends Model implements ArticlaiConnectable, HasMedia
{
    use ArticlaiConnector;
    use HasFactory;
    use InteractsWithMedia {
        InteractsWithMedia::bootInteractsWithMedia as protected bootInteractsWithMediaTrait;
    }

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
     * Get the URL for this post (can be overridden in implementation)
     */
    public function getUrlAttribute(): string
    {
        return $this->getUrl();
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

    /**
     * Boot the InteractsWithMedia trait, but skip in testing
     */
    protected static function bootInteractsWithMedia()
    {
        if (!app()->environment('testing')) {
            static::bootInteractsWithMediaTrait();
        }
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('banner')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
    }

    /**
     * Register media conversions
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->fit(Fit::Crop, 150, 150)
            ->quality(80)
            ->performOnCollections('banner');

        $this->addMediaConversion('medium')
            ->fit(Fit::Crop, 300, 300)
            ->quality(85)
            ->performOnCollections('banner');

        $this->addMediaConversion('large')
            ->fit(Fit::Crop, 800, 600)
            ->quality(90)
            ->performOnCollections('banner');
    }

    public function getBannerLink()
    {
        return $this->getFirstMediaUrl('banner');
    }

    /**
     * Get banner image URL
     */
    public function getBannerImageAttribute(): ?string
    {
        if (app()->environment('testing')) {
            return null;
        }

        return $this->getFirstMediaUrl('banner', 'large');
    }

    /**
     * Get banner thumbnail URL
     */
    public function getBannerThumbnailAttribute(): ?string
    {
        if (app()->environment('testing')) {
            return null;
        }

        return $this->getFirstMediaUrl('banner', 'thumbnail');
    }

    /**
     * Get banner medium URL
     */
    public function getBannerMediumAttribute(): ?string
    {
        if (app()->environment('testing')) {
            return null;
        }

        return $this->getFirstMediaUrl('banner', 'medium');
    }

    /**
     * Get banner large URL
     */
    public function getBannerLargeAttribute(): ?string
    {
        if (app()->environment('testing')) {
            return null;
        }

        return $this->getFirstMediaUrl('banner', 'large');
    }

    /**
     * Get banner original URL
     */
    public function getBannerOriginalAttribute(): ?string
    {
        if (app()->environment('testing')) {
            return null;
        }

        return $this->getFirstMediaUrl('banner');
    }

    /**
     * Add banner image from URL
     */
    public function addBannerFromUrl(string $url): void
    {
        // Skip media operations in testing environment
        if (app()->environment('testing')) {
            return;
        }

        try {
            $this->addMediaFromUrl($url)
                ->toMediaCollection('banner');
        } catch (\Exception $e) {
            // Log the error but don't fail the entire operation
            \Log::warning('Failed to download banner image from URL: ' . $url, [
                'error' => $e->getMessage(),
                'post_id' => $this->id,
            ]);
        }
    }
}
