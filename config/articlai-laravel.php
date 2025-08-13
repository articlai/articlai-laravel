<?php

// config for Articlai/Articlai
return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how ArticlAI will authenticate with your application.
    | Supported methods: 'api_key', 'bearer_token', 'basic_auth'
    |
    */
    'auth' => [
        'method' => env('ARTICLAI_AUTH_METHOD', 'api_key'),
        'api_key' => env('ARTICLAI_API_KEY'),
        'bearer_token' => env('ARTICLAI_BEARER_TOKEN'),
        'basic_auth' => [
            'username' => env('ARTICLAI_BASIC_USERNAME'),
            'password' => env('ARTICLAI_BASIC_PASSWORD'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the API endpoints and behavior
    |
    */
    'api' => [
        'prefix' => 'api/articlai',
        'middleware' => ['api', 'articlai.auth'],
        'rate_limit' => env('ARTICLAI_RATE_LIMIT', 60), // requests per minute
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how content is processed and stored
    |
    */
    'content' => [
        'default_status' => env('ARTICLAI_DEFAULT_STATUS', 'published'),
        'allowed_statuses' => ['draft', 'published', 'private', 'trash'],
        'auto_generate_slug' => env('ARTICLAI_AUTO_GENERATE_SLUG', true),
        'sanitize_html' => env('ARTICLAI_SANITIZE_HTML', true),
        'allowed_html_tags' => [
            'p', 'br', 'strong', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'ul', 'ol', 'li', 'a', 'img', 'blockquote', 'code', 'pre',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform Information
    |--------------------------------------------------------------------------
    |
    | Information about your platform for the validation endpoint
    |
    */
    'platform' => [
        'name' => env('ARTICLAI_PLATFORM_NAME', 'Laravel Application'),
        'version' => env('ARTICLAI_PLATFORM_VERSION', '1.0.0'),
        'capabilities' => ['create', 'update', 'delete'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which model to use for blog posts and how to map fields
    |
    */
    'model' => [
        // The model class to use for blog posts
        'class' => env('ARTICLAI_MODEL_CLASS', \Articlai\Articlai\Models\ArticlaiPost::class),

        // Enable media support (requires spatie/laravel-medialibrary)
        'enable_media' => env('ARTICLAI_ENABLE_MEDIA', true),

        // URL prefix for blog post URLs (e.g., /blog/post-slug)
        'url_prefix' => env('ARTICLAI_URL_PREFIX', 'blog'),

        // Global field mapping (maps Articlai fields to your model fields)
        // You can also define this per-model by adding an $articlaiFieldMapping property
        'field_mapping' => [
            'title' => 'title',
            'content' => 'content',
            'excerpt' => 'excerpt',
            'slug' => 'slug',
            'meta_title' => 'meta_title',
            'meta_description' => 'meta_description',
            'focus_keyword' => 'focus_keyword',
            'canonical_url' => 'canonical_url',
            'published_at' => 'published_at',
            'publish_date' => 'published_at', // Map publish_date to published_at
            'custom_fields' => 'custom_fields',
            'status' => 'status',
            // 'banner_url' => 'featured_image_url', // Example: if you store banner URL in a field
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the database table and model behavior
    |
    */
    'database' => [
        'table_name' => 'blogs',
        'connection' => env('ARTICLAI_DB_CONNECTION', null),
    ],
];
