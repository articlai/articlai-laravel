# Articlai Model Connector Guide

The Articlai Laravel package now supports connecting your existing blog models to the Articlai system without disrupting your current structure. This optional connector allows you to use your own models while maintaining compatibility with the Articlai API.

## Quick Start

### 1. Add the Trait to Your Model

Add the `ArticlaiConnector` trait and implement the `ArticlaiConnectable` interface in your existing blog model:

```php
<?php

namespace App\Models;

use Articlai\Articlai\Contracts\ArticlaiConnectable;
use Articlai\Articlai\Traits\ArticlaiConnector;
use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model implements ArticlaiConnectable
{
    use ArticlaiConnector;
    
    protected $fillable = [
        'title', 'body', 'slug', 'status', 'published_at'
        // ... your existing fields
    ];
}
```

### 2. Configure the Model

Update your `config/articlai-laravel.php` configuration:

```php
'model' => [
    'class' => \App\Models\BlogPost::class,
    'field_mapping' => [
        'title' => 'title',
        'content' => 'body',  // Map Articlai's 'content' to your 'body' field
        'slug' => 'slug',
        'status' => 'status',
        'published_at' => 'published_at',
        // Add other field mappings as needed
    ],
],
```

### 3. That's It!

Your existing model is now connected to Articlai. All API endpoints will work with your model while preserving your existing data structure.

## Field Mapping

The connector uses field mapping to translate between Articlai's expected fields and your model's actual fields.

### Default Field Mapping

```php
[
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
]
```

### Custom Field Mapping

You can customize field mapping in three ways:

#### 1. Global Configuration (config/articlai-laravel.php)

```php
'model' => [
    'field_mapping' => [
        'title' => 'post_title',
        'content' => 'post_body',
        'excerpt' => 'post_excerpt',
        'slug' => 'post_slug',
        // ... other mappings
    ],
],
```

#### 2. Model Property

Add a property to your model:

```php
class BlogPost extends Model implements ArticlaiConnectable
{
    use ArticlaiConnector;
    
    protected $articlaiFieldMapping = [
        'title' => 'post_title',
        'content' => 'post_body',
        'excerpt' => 'post_excerpt',
    ];
}
```

#### 3. Per-Model Configuration

Configure mapping for specific model classes:

```php
'model' => [
    'field_mapping' => [
        \App\Models\BlogPost::class => [
            'title' => 'post_title',
            'content' => 'post_body',
        ],
        \App\Models\Article::class => [
            'title' => 'article_title',
            'content' => 'article_content',
        ],
    ],
],
```

## Media Support

### With Spatie Media Library

If your model uses `spatie/laravel-medialibrary`, banner image functionality will work automatically:

```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class BlogPost extends Model implements HasMedia, ArticlaiConnectable
{
    use InteractsWithMedia;
    use ArticlaiConnector;
    
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('banner')->singleFile();
    }
}
```

### Without Media Library

If you store banner images as URLs in a database field:

```php
'model' => [
    'field_mapping' => [
        'banner_url' => 'featured_image_url',
        // ... other mappings
    ],
],
```

### Disable Media Support

```php
'model' => [
    'enable_media' => false,
],
```

## Advanced Configuration

### Custom URL Generation

Override the URL generation in your model:

```php
class BlogPost extends Model implements ArticlaiConnectable
{
    use ArticlaiConnector;
    
    public function getUrl(): string
    {
        return route('blog.show', $this->slug);
    }
}
```

### Custom Status Logic

Override the published status logic:

```php
class BlogPost extends Model implements ArticlaiConnectable
{
    use ArticlaiConnector;
    
    public function isPublished(): bool
    {
        return $this->status === 'live' && $this->publish_date <= now();
    }
}
```

### Custom Slug Generation

Override slug generation:

```php
class BlogPost extends Model implements ArticlaiConnectable
{
    use ArticlaiConnector;
    
    public function generateUniqueSlug(string $title): string
    {
        // Your custom slug generation logic
        return Str::slug($title) . '-' . time();
    }
}
```

## Environment Variables

You can configure the connector using environment variables:

```env
# Model Configuration
ARTICLAI_MODEL_CLASS=App\Models\BlogPost
ARTICLAI_ENABLE_MEDIA=true
ARTICLAI_URL_PREFIX=articles

# Other existing variables
ARTICLAI_API_KEY=your-api-key
ARTICLAI_AUTH_METHOD=api_key
```

## Migration from Default Model

If you're currently using the default `ArticlaiPost` model and want to switch to your own model:

1. **Backup your data** from the `blogs` table
2. **Create your new model** with the connector trait
3. **Configure field mapping** to match your new model's structure
4. **Migrate your data** to the new table structure
5. **Update the configuration** to use your new model class

## Troubleshooting

### Model Not Found Error

```
Configured model class 'App\Models\BlogPost' does not exist.
```

**Solution**: Ensure your model class exists and is properly namespaced.

### Interface Not Implemented Error

```
Configured model class must implement ArticlaiConnectable interface.
```

**Solution**: Add `implements ArticlaiConnectable` to your model class.

### Missing Trait Error

```
Call to undefined method getArticlaiData()
```

**Solution**: Add `use ArticlaiConnector;` to your model class.

### Field Mapping Issues

If data isn't being saved correctly, check your field mapping configuration. The mapping should go from Articlai field names (keys) to your model field names (values).

## Examples

### Example 1: WordPress-style Model

```php
class WpPost extends Model implements ArticlaiConnectable
{
    use ArticlaiConnector;
    
    protected $table = 'wp_posts';
    
    protected $articlaiFieldMapping = [
        'title' => 'post_title',
        'content' => 'post_content',
        'excerpt' => 'post_excerpt',
        'slug' => 'post_name',
        'status' => 'post_status',
        'published_at' => 'post_date',
    ];
    
    public function isPublished(): bool
    {
        return $this->post_status === 'publish';
    }
}
```

### Example 2: Custom Blog Model

```php
class Article extends Model implements ArticlaiConnectable
{
    use ArticlaiConnector;
    
    protected $fillable = [
        'headline', 'body', 'summary', 'url_slug', 
        'is_published', 'publish_date'
    ];
    
    protected $articlaiFieldMapping = [
        'title' => 'headline',
        'content' => 'body',
        'excerpt' => 'summary',
        'slug' => 'url_slug',
        'published_at' => 'publish_date',
    ];
    
    public function isPublished(): bool
    {
        return $this->is_published && $this->publish_date <= now();
    }
    
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where('publish_date', '<=', now());
    }
}
```

## Support

If you encounter any issues with the connector, please check:

1. Your model implements the `ArticlaiConnectable` interface
2. Your model uses the `ArticlaiConnector` trait
3. Your field mapping is correctly configured
4. Your model class is properly registered in the configuration

For additional support, please refer to the main package documentation or create an issue in the repository.
