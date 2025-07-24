<?php

namespace Articlai\Articlai\Services;

use Articlai\Articlai\Contracts\ArticlaiConnectable;
use Articlai\Articlai\Exceptions\ArticlaiException;
use Illuminate\Database\Eloquent\Model;

class ModelResolver
{
    protected string $modelClass;

    public function __construct()
    {
        $this->modelClass = config('articlai-laravel.model.class', \Articlai\Articlai\Models\ArticlaiPost::class);

        $this->validateModelClass();
    }

    /**
     * Create a new model instance
     */
    public function create(array $data): ArticlaiConnectable
    {
        $model = $this->newInstance();
        $model->setArticlaiData($data);

        // Generate slug if not provided and title is available
        $articlaiData = $model->getArticlaiData();
        if (empty($articlaiData['slug']) && ! empty($articlaiData['title'])) {
            $mapping = $model->getArticlaiFieldMapping();
            $slugField = $mapping['slug'] ?? 'slug';
            $slug = $model->generateUniqueSlug($articlaiData['title']);
            $model->setAttribute($slugField, $slug);
        }

        $model->save();

        return $model;
    }

    /**
     * Find a model by ID
     */
    public function find($id): ?ArticlaiConnectable
    {
        return $this->modelClass::find($id);
    }

    /**
     * Find a model by ID or fail
     */
    public function findOrFail($id): ArticlaiConnectable
    {
        return $this->modelClass::findOrFail($id);
    }

    /**
     * Update a model with the given data
     */
    public function update(ArticlaiConnectable $model, array $data): ArticlaiConnectable
    {
        $model->setArticlaiData($data);
        $model->save();

        return $model;
    }

    /**
     * Delete a model
     */
    public function delete(ArticlaiConnectable $model): bool
    {
        return $model->delete();
    }

    /**
     * Get a paginated list of models
     */
    public function paginate(int $perPage = 15)
    {
        return $this->modelClass::orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Create a new model instance without saving
     */
    public function newInstance(array $attributes = []): ArticlaiConnectable
    {
        return new $this->modelClass($attributes);
    }

    /**
     * Get the model class being used
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * Check if the configured model class is valid
     */
    protected function validateModelClass(): void
    {
        if (! class_exists($this->modelClass)) {
            throw ArticlaiException::serverError("Configured model class '{$this->modelClass}' does not exist.");
        }

        if (! is_subclass_of($this->modelClass, Model::class)) {
            throw ArticlaiException::serverError("Configured model class '{$this->modelClass}' must extend Illuminate\\Database\\Eloquent\\Model.");
        }

        // Check if the model implements the ArticlaiConnectable interface
        if (! in_array(ArticlaiConnectable::class, class_implements($this->modelClass))) {
            throw ArticlaiException::serverError("Configured model class '{$this->modelClass}' must implement ArticlaiConnectable interface.");
        }
    }

    /**
     * Get query builder for the model
     */
    public function query()
    {
        return $this->modelClass::query();
    }

    /**
     * Apply published scope to query
     */
    public function published()
    {
        return $this->modelClass::published();
    }

    /**
     * Apply status scope to query
     */
    public function byStatus(string $status)
    {
        return $this->modelClass::byStatus($status);
    }
}
