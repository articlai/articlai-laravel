<?php

namespace Articlai\Articlai\Traits;

use Illuminate\Support\Str;

trait ArticlaiConnector
{
    /**
     * Get data formatted for Articlai API responses
     */
    public function getArticlaiData(): array
    {
        $mapping = $this->getArticlaiFieldMapping();
        $data = [];

        foreach ($mapping as $articlaiField => $modelField) {
            if ($modelField && isset($this->attributes[$modelField])) {
                $data[$articlaiField] = $this->attributes[$modelField];
            }
        }

        // Add computed fields
        $data['url'] = $this->getUrl();
        $data['is_published'] = $this->isPublished();

        // Add banner image if media support is enabled
        if ($this->hasMediaSupport()) {
            $data['banner_image'] = $this->getBannerImage();
            $data['banner_thumbnail'] = $this->getBannerThumbnail();
            $data['banner_medium'] = $this->getBannerMedium();
            $data['banner_large'] = $this->getBannerLarge();
            $data['banner_original'] = $this->getBannerOriginal();
        }

        return $data;
    }

    /**
     * Set data from Articlai API requests with field mapping
     */
    public function setArticlaiData(array $data): void
    {
        $mapping = $this->getArticlaiFieldMapping();
        $mappedData = [];

        foreach ($data as $articlaiField => $value) {
            if (isset($mapping[$articlaiField]) && $mapping[$articlaiField]) {
                $mappedData[$mapping[$articlaiField]] = $value;
            }
        }

        $this->fill($mappedData);
    }

    /**
     * Generate a unique slug from the title
     */
    public function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        $mapping = $this->getArticlaiFieldMapping();
        $slugField = $mapping['slug'] ?? 'slug';

        while (static::where($slugField, $slug)->where($this->getKeyName(), '!=', $this->getKey() ?? 0)->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if the post is published
     */
    public function isPublished(): bool
    {
        $mapping = $this->getArticlaiFieldMapping();
        $statusField = $mapping['status'] ?? 'status';
        $publishedAtField = $mapping['published_at'] ?? 'published_at';

        $status = $this->getAttribute($statusField);
        $publishedAt = $this->getAttribute($publishedAtField);

        return $status === 'published' &&
               ($publishedAt === null || $publishedAt <= now());
    }

    /**
     * Get the URL for this post
     */
    public function getUrl(): string
    {
        $mapping = $this->getArticlaiFieldMapping();
        $slugField = $mapping['slug'] ?? 'slug';
        $slug = $this->getAttribute($slugField);

        $baseUrl = config('app.url');
        $urlPrefix = config('articlai-laravel.model.url_prefix', 'blog');

        return rtrim($baseUrl, '/').'/'.$urlPrefix.'/'.$slug;
    }

    /**
     * Scope to filter published posts
     */
    public function scopePublished($query)
    {
        $mapping = $this->getArticlaiFieldMapping();
        $statusField = $mapping['status'] ?? 'status';
        $publishedAtField = $mapping['published_at'] ?? 'published_at';

        return $query->where($statusField, 'published')
            ->where(function ($q) use ($publishedAtField) {
                $q->whereNull($publishedAtField)
                    ->orWhere($publishedAtField, '<=', now());
            });
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        $mapping = $this->getArticlaiFieldMapping();
        $statusField = $mapping['status'] ?? 'status';

        return $query->where($statusField, $status);
    }

    /**
     * Get the field mapping configuration for this model
     */
    public function getArticlaiFieldMapping(): array
    {
        // Check if the model has a custom mapping defined
        if (property_exists($this, 'articlaiFieldMapping')) {
            return array_merge($this->getDefaultFieldMapping(), $this->articlaiFieldMapping);
        }

        // Check config for model-specific mapping
        $modelClass = get_class($this);
        $configMapping = config("articlai-laravel.model.field_mapping.{$modelClass}", []);

        if (!empty($configMapping)) {
            return array_merge($this->getDefaultFieldMapping(), $configMapping);
        }

        // Use global field mapping from config
        $globalMapping = config('articlai-laravel.model.field_mapping', []);

        return array_merge($this->getDefaultFieldMapping(), $globalMapping);
    }

    /**
     * Get the default field mapping
     */
    protected function getDefaultFieldMapping(): array
    {
        return [
            'title' => 'title',
            'content' => 'content',
            'excerpt' => 'excerpt',
            'slug' => 'slug',
            'meta_title' => 'meta_title',
            'meta_description' => 'meta_description',
            'focus_keyword' => 'focus_keyword',
            'canonical_url' => 'canonical_url',
            'published_at' => 'published_at',
            'custom_fields' => 'custom_fields',
            'status' => 'status',
        ];
    }

    /**
     * Check if this model supports media functionality
     */
    protected function hasMediaSupport(): bool
    {
        return config('articlai-laravel.model.enable_media', true) &&
               in_array('Spatie\MediaLibrary\InteractsWithMedia', class_uses_recursive($this));
    }

    /**
     * Add banner image from URL (default implementation for models without media support)
     */
    public function addBannerFromUrl(string $url): void
    {
        if (!$this->hasMediaSupport()) {
            // Store URL in a banner_url field if it exists
            $mapping = $this->getArticlaiFieldMapping();
            if (isset($mapping['banner_url'])) {
                $this->setAttribute($mapping['banner_url'], $url);
                $this->save();
            }
            return;
        }

        // Skip media operations in testing environment
        if (app()->environment('testing')) {
            return;
        }

        try {
            $this->addMediaFromUrl($url)
                ->toMediaCollection('banner');
        } catch (\Exception $e) {
            \Log::warning('Failed to download banner image from URL: ' . $url, [
                'error' => $e->getMessage(),
                'model_id' => $this->getKey(),
                'model_class' => get_class($this)
            ]);
        }
    }

    /**
     * Get banner image URL
     */
    public function getBannerImage(): ?string
    {
        if (!$this->hasMediaSupport()) {
            // Try to get from banner_url field
            $mapping = $this->getArticlaiFieldMapping();
            if (isset($mapping['banner_url'])) {
                return $this->getAttribute($mapping['banner_url']);
            }
            return null;
        }

        if (app()->environment('testing')) {
            return null;
        }

        return $this->getFirstMediaUrl('banner', 'large');
    }

    /**
     * Get banner thumbnail URL
     */
    public function getBannerThumbnail(): ?string
    {
        if (!$this->hasMediaSupport() || app()->environment('testing')) {
            return null;
        }
        return $this->getFirstMediaUrl('banner', 'thumbnail');
    }

    /**
     * Get banner medium URL
     */
    public function getBannerMedium(): ?string
    {
        if (!$this->hasMediaSupport() || app()->environment('testing')) {
            return null;
        }
        return $this->getFirstMediaUrl('banner', 'medium');
    }

    /**
     * Get banner large URL
     */
    public function getBannerLarge(): ?string
    {
        if (!$this->hasMediaSupport() || app()->environment('testing')) {
            return null;
        }
        return $this->getFirstMediaUrl('banner', 'large');
    }

    /**
     * Get banner original URL
     */
    public function getBannerOriginal(): ?string
    {
        if (!$this->hasMediaSupport() || app()->environment('testing')) {
            return null;
        }
        return $this->getFirstMediaUrl('banner');
    }
}
