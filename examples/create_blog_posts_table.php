<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Example migration for creating a blog_posts table compatible with Articlai
 * 
 * This migration creates a table structure that works well with the Articlai connector.
 * You can customize the field names and types according to your needs, then update
 * the field mapping in your model or configuration.
 * 
 * To use this migration:
 * 1. Copy this file to your database/migrations directory
 * 2. Rename it with a proper timestamp (e.g., 2024_01_01_000000_create_blog_posts_table.php)
 * 3. Run: php artisan migrate
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            
            // Core content fields
            $table->string('post_title');
            $table->longText('post_content');
            $table->text('post_excerpt')->nullable();
            $table->string('post_slug')->unique();
            
            // SEO fields
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('focus_keyword')->nullable();
            $table->string('canonical_url')->nullable();
            
            // Publishing fields
            $table->enum('post_status', ['draft', 'published', 'private', 'trash'])->default('draft');
            $table->timestamp('published_date')->nullable();
            
            // Media field (if not using spatie/laravel-medialibrary)
            $table->string('featured_image_url')->nullable();
            
            // Additional fields
            $table->json('custom_fields')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('post_status');
            $table->index('published_date');
            $table->index('post_slug');
            $table->index(['post_status', 'published_date']); // Composite index for published posts
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
