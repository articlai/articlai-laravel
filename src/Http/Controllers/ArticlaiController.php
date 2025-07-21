<?php

namespace Articlai\Articlai\Http\Controllers;

use Articlai\Articlai\Articlai;
use Articlai\Articlai\Exceptions\ArticlaiException;
use Articlai\Articlai\Http\Requests\CreatePostRequest;
use Articlai\Articlai\Http\Requests\UpdatePostRequest;
use Articlai\Articlai\Http\Resources\PostResource;
use Articlai\Articlai\Models\ArticlaiPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ArticlaiController extends Controller
{
    protected $articlaiService;

    public function __construct(Articlai $articlaiService)
    {
        $this->articlaiService = $articlaiService;
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

            // Sanitize content if enabled
            if (config('articlai-laravel.content.sanitize_html', true)) {
                $validatedData['content'] = $this->articlaiService->sanitizeContent($validatedData['content']);
            }

            $post = ArticlaiPost::create($validatedData);

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
            $post = ArticlaiPost::findOrFail($id);

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
            $post = ArticlaiPost::findOrFail($id);
            $validatedData = $request->validated();

            // Sanitize content if enabled and content is being updated
            if (isset($validatedData['content']) && config('articlai-laravel.content.sanitize_html', true)) {
                $validatedData['content'] = $this->articlaiService->sanitizeContent($validatedData['content']);
            }

            $post->update($validatedData);

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
            $post = ArticlaiPost::findOrFail($id);
            $post->delete();

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
            $posts = ArticlaiPost::orderBy('created_at', 'desc')->paginate(15);

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
