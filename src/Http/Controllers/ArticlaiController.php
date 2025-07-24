<?php

namespace Articlai\Articlai\Http\Controllers;

use Articlai\Articlai\Articlai;
use Articlai\Articlai\Exceptions\ArticlaiException;
use Articlai\Articlai\Http\Requests\CreatePostRequest;
use Articlai\Articlai\Http\Requests\UpdatePostRequest;
use Articlai\Articlai\Http\Resources\PostResource;
use Articlai\Articlai\Services\ModelResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ArticlaiController extends Controller
{
    protected $articlaiService;
    protected $modelResolver;

    public function __construct(Articlai $articlaiService, ModelResolver $modelResolver)
    {
        $this->articlaiService = $articlaiService;
        $this->modelResolver = $modelResolver;
    }

    /**
     * § the connection
     */
    public function validate(): JsonResponse
    {
        try {
            return response()->json(PostResource::validation());
        } catch (\Exception $e) {
            throw ArticlaiException::serverError('Validation failed: '.$e->getMessage());
        }
    }

    /**
     * Create a new post
     */
    public function store(CreatePostRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            // Extract banner image URLs
            $bannerImageUrl = $validatedData['banner_image'] ?? $validatedData['banner_original'] ?? null;

            // Remove banner URLs from data before creating post
            $postData = collect($validatedData)->except([
                'banner_image', 'banner_thumbnail', 'banner_medium', 'banner_large', 'banner_original'
            ])->toArray();

            // Sanitize content if enabled
            if (config('articlai-laravel.content.sanitize_html', true)) {
                $postData['content'] = $this->articlaiService->sanitizeContent($postData['content']);
            }

            $post = $this->modelResolver->create($postData);

            // Add banner image if provided
            if ($bannerImageUrl) {
                $post->addBannerFromUrl($bannerImageUrl);
            }

            return response()->json(PostResource::created($post), 201);
        } catch (ArticlaiException $e) {
            return $e->render();
        } catch (\Exception $e) {
            throw ArticlaiException::serverError('Failed to create post: '.$e->getMessage());
        }
    }

    /**
     * Show a specific post
     */
    public function show(string $id): JsonResponse
    {
        try {
            $post = $this->modelResolver->findOrFail($id);

            return response()->json(new PostResource($post));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw ArticlaiException::notFound('Post');
        } catch (\Exception $e) {
            throw ArticlaiException::serverError('Failed to retrieve post: '.$e->getMessage());
        }
    }

    /**
     * Update an existing post
     */
    public function update(UpdatePostRequest $request, string $id): JsonResponse
    {
        try {
            $post = $this->modelResolver->findOrFail($id);
            $validatedData = $request->validated();

            // Extract banner image URLs
            $bannerImageUrl = $validatedData['banner_image'] ?? $validatedData['banner_original'] ?? null;

            // Remove banner URLs from data before updating post
            $postData = collect($validatedData)->except([
                'banner_image', 'banner_thumbnail', 'banner_medium', 'banner_large', 'banner_original'
            ])->toArray();

            // Sanitize content if enabled and content is being updated
            if (isset($postData['content']) && config('articlai-laravel.content.sanitize_html', true)) {
                $postData['content'] = $this->articlaiService->sanitizeContent($postData['content']);
            }

            $post = $this->modelResolver->update($post, $postData);

            // Update banner image if provided
            if ($bannerImageUrl && !app()->environment('testing')) {
                // Clear existing banner media if the model supports it
                if (method_exists($post, 'clearMediaCollection')) {
                    $post->clearMediaCollection('banner');
                }
                // Add new banner image
                $post->addBannerFromUrl($bannerImageUrl);
            }

            return response()->json(PostResource::updated($post));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw ArticlaiException::notFound('Post');
        } catch (ArticlaiException $e) {
            return $e->render();
        } catch (\Exception $e) {
            throw ArticlaiException::serverError('Failed to update post: '.$e->getMessage());
        }
    }

    /**
     * Delete a post
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $post = $this->modelResolver->findOrFail($id);
            $this->modelResolver->delete($post);

            return response()->json(PostResource::deleted());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw ArticlaiException::notFound('Post');
        } catch (\Exception $e) {
            throw ArticlaiException::serverError('Failed to delete post: '.$e->getMessage());
        }
    }

    /**
     * List all posts (optional endpoint for debugging)
     */
    public function index(): JsonResponse
    {
        try {
            $posts = $this->modelResolver->paginate(15);

            return response()->json([
                'data' => PostResource::collection($posts->items()),
                'meta' => [
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                    'per_page' => $posts->perPage(),
                    'total' => $posts->total(),
                ],
            ]);
        } catch (\Exception $e) {
            throw ArticlaiException::serverError('Failed to retrieve posts: '.$e->getMessage());
        }
    }
}
