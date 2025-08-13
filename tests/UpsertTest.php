<?php

use Articlai\Articlai\Models\ArticlaiPost;
use Articlai\Articlai\Services\ModelResolver;

it('creates new post when slug does not exist', function () {
    $resolver = new ModelResolver;

    $data = [
        'title' => 'Test Post',
        'content' => 'This is test content',
        'slug' => 'test-post',
        'status' => 'published',
    ];

    $result = $resolver->upsert($data);

    expect($result['was_updated'])->toBeFalse();
    expect($result['post'])->toBeInstanceOf(ArticlaiPost::class);
    expect($result['post']->slug)->toBe('test-post');
    expect($result['post']->title)->toBe('Test Post');
    $this->assertDatabaseHas('blogs', ['slug' => 'test-post', 'title' => 'Test Post']);
});

it('updates existing post when slug already exists', function () {
    // Create an existing post
    $existingPost = ArticlaiPost::create([
        'title' => 'Original Title',
        'content' => 'Original content',
        'slug' => 'test-post',
        'status' => 'draft',
    ]);

    $resolver = new ModelResolver;

    $data = [
        'title' => 'Updated Title',
        'content' => 'Updated content',
        'slug' => 'test-post',
        'status' => 'published',
    ];

    $result = $resolver->upsert($data);

    expect($result['was_updated'])->toBeTrue();
    expect($result['post'])->toBeInstanceOf(ArticlaiPost::class);
    expect($result['post']->id)->toBe($existingPost->id);
    expect($result['post']->slug)->toBe('test-post');
    expect($result['post']->title)->toBe('Updated Title');
    expect($result['post']->content)->toBe('Updated content');
    expect($result['post']->status)->toBe('published');

    // Verify only one post exists with this slug
    expect(ArticlaiPost::where('slug', 'test-post')->count())->toBe(1);
    $this->assertDatabaseHas('blogs', ['slug' => 'test-post', 'title' => 'Updated Title']);
    $this->assertDatabaseMissing('blogs', ['slug' => 'test-post', 'title' => 'Original Title']);
});

it('creates new post when no slug provided', function () {
    $resolver = new ModelResolver;

    $data = [
        'title' => 'Test Post Without Slug',
        'content' => 'This is test content',
        'status' => 'published',
    ];

    $result = $resolver->upsert($data);

    expect($result['was_updated'])->toBeFalse();
    expect($result['post'])->toBeInstanceOf(ArticlaiPost::class);
    expect($result['post']->slug)->toBe('test-post-without-slug'); // Auto-generated
    expect($result['post']->title)->toBe('Test Post Without Slug');
});

it('finds existing post by slug correctly', function () {
    // Create an existing post
    $existingPost = ArticlaiPost::create([
        'title' => 'Existing Post',
        'content' => 'Existing content',
        'slug' => 'existing-post',
        'status' => 'published',
    ]);

    $resolver = new ModelResolver;

    $foundPost = $resolver->findBySlug('existing-post');

    expect($foundPost)->not->toBeNull();
    expect($foundPost->id)->toBe($existingPost->id);
    expect($foundPost->slug)->toBe('existing-post');

    $notFoundPost = $resolver->findBySlug('non-existent-slug');
    expect($notFoundPost)->toBeNull();
});

it('handles resync through API endpoint correctly', function () {
    // Create an existing post
    $existingPost = ArticlaiPost::create([
        'title' => 'Original API Post',
        'content' => 'Original content',
        'slug' => 'api-test-post',
        'status' => 'draft',
    ]);

    // Simulate a resync request with the same slug but updated content
    $postData = [
        'title' => 'Updated API Post',
        'content' => 'Updated content via API',
        'slug' => 'api-test-post',
        'status' => 'published',
    ];

    $response = $this->postJson('/api/articlai/posts', $postData, [
        'X-API-Key' => 'test-api-key',
    ]);

    $response->assertStatus(200); // Should return 200 for update, not 201 for create
    $response->assertJson([
        'id' => (string) $existingPost->id,
        'title' => 'Updated API Post',
        'status' => 'published',
    ]);

    // Verify the existing post was updated, not a new one created
    expect(ArticlaiPost::where('slug', 'api-test-post')->count())->toBe(1);

    $updatedPost = ArticlaiPost::where('slug', 'api-test-post')->first();
    expect($updatedPost->id)->toBe($existingPost->id);
    expect($updatedPost->title)->toBe('Updated API Post');
    expect($updatedPost->content)->toBe('Updated content via API');
    expect($updatedPost->status)->toBe('published');
});

it('handles publish_date field correctly', function () {
    $publishDate = '2024-01-15T10:30:00Z';

    $postData = [
        'title' => 'Post with Publish Date',
        'content' => 'Content with publish date',
        'slug' => 'post-with-publish-date',
        'status' => 'published',
        'publish_date' => $publishDate,
    ];

    $response = $this->postJson('/api/articlai/posts', $postData, [
        'X-API-Key' => 'test-api-key',
    ]);

    $response->assertStatus(201);
    $response->assertJson([
        'title' => 'Post with Publish Date',
        'status' => 'published',
    ]);

    // Verify the post was created with the correct publish date
    $post = ArticlaiPost::where('slug', 'post-with-publish-date')->first();
    expect($post)->not->toBeNull();
    expect($post->published_at)->not->toBeNull();
    expect($post->published_at->format('Y-m-d\TH:i:s\Z'))->toBe($publishDate);
});

it('handles both published_at and publish_date fields', function () {
    $publishDate = '2024-02-20T14:45:00Z';

    $postData = [
        'title' => 'Post with Both Date Fields',
        'content' => 'Content with both date fields',
        'slug' => 'post-with-both-dates',
        'status' => 'published',
        'published_at' => '2024-01-01T00:00:00Z',
        'publish_date' => $publishDate, // This should take precedence due to field mapping
    ];

    $response = $this->postJson('/api/articlai/posts', $postData, [
        'X-API-Key' => 'test-api-key',
    ]);

    $response->assertStatus(201);

    // Verify the post was created with the publish_date value
    $post = ArticlaiPost::where('slug', 'post-with-both-dates')->first();
    expect($post)->not->toBeNull();
    expect($post->published_at->format('Y-m-d\TH:i:s\Z'))->toBe($publishDate);
});

it('includes publish_date in API response', function () {
    $post = ArticlaiPost::create([
        'title' => 'Test Post for API Response',
        'content' => 'Test content',
        'slug' => 'test-api-response',
        'status' => 'published',
        'published_at' => '2024-03-15T12:00:00Z',
        'custom_fields' => [],
    ]);

    $response = $this->getJson("/api/articlai/posts/{$post->id}", [
        'X-API-Key' => 'test-api-key',
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'id',
        'title',
        'content',
        'slug',
        'status',
        'published_at',
        'publish_date', // Should be included in response
        'created_at',
        'updated_at',
    ]);

    $responseData = $response->json();
    expect($responseData['publish_date'])->not->toBeNull();
    expect($responseData['published_at'])->toBe($responseData['publish_date']); // Should be the same
});
