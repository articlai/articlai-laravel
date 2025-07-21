<?php

use Articlai\Articlai\Models\ArticlaiPost;
use Articlai\Articlai\Http\Resources\PostResource;

it('can create a post', function () {
    $postData = [
        'title' => 'Test Post',
        'content' => '<p>This is test content</p>',
        'excerpt' => 'Test excerpt',
        'slug' => 'test-post',
        'status' => 'published',
    ];

    $response = $this->postJson('/api/articlai/posts', $postData, [
        'X-API-Key' => 'test-api-key'
    ]);

    $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'url',
                'title',
                'status',
                'created_at'
            ]);

    $this->assertDatabaseHas('blogs', [
        'title' => 'Test Post',
        'slug' => 'test-post',
    ]);
});

it('can validate connection', function () {
    $response = $this->getJson('/api/articlai/validate', [
        'X-API-Key' => 'test-api-key'
    ]);

    $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Connection validated successfully',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'platform_info' => [
                    'name',
                    'version',
                    'capabilities'
                ]
            ]);
});

it('requires authentication', function () {
    $response = $this->postJson('/api/articlai/posts', [
        'title' => 'Test Post',
        'content' => 'Test content',
    ]);

    $response->assertStatus(401);
});

it('validates required fields', function () {
    $response = $this->postJson('/api/articlai/posts', [], [
        'X-API-Key' => 'test-api-key'
    ]);

    $response->assertStatus(422)
            ->assertJsonStructure([
                'error',
                'code',
                'details'
            ]);
});

it('can update a post', function () {
    $post = ArticlaiPost::factory()->create([
        'title' => 'Original Title',
        'content' => 'Original content',
    ]);

    $updateData = [
        'title' => 'Updated Title',
        'content' => 'Updated content',
    ];

    $response = $this->putJson("/api/articlai/posts/{$post->id}", $updateData, [
        'X-API-Key' => 'test-api-key'
    ]);

    $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'url',
                'title',
                'status',
                'updated_at'
            ]);

    $this->assertDatabaseHas('blogs', [
        'id' => $post->id,
        'title' => 'Updated Title',
    ]);
});

it('can delete a post', function () {
    $post = ArticlaiPost::factory()->create();

    $response = $this->deleteJson("/api/articlai/posts/{$post->id}", [], [
        'X-API-Key' => 'test-api-key'
    ]);

    $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Post deleted successfully',
            ]);

    $this->assertDatabaseMissing('blogs', [
        'id' => $post->id,
    ]);
});
