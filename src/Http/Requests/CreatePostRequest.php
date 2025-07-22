<?php

namespace Articlai\Articlai\Http\Requests;

use Articlai\Articlai\Exceptions\ArticlaiException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CreatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:1000',
            'slug' => 'nullable|string|max:255|regex:/^[a-z0-9-]+$/|unique:blogs,slug',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'focus_keyword' => 'nullable|string|max:100',
            'canonical_url' => 'nullable|url|max:500',
            'published_at' => 'nullable|date',
            'custom_fields' => 'nullable|array',
            'status' => 'nullable|string|in:'.implode(',', config('articlai-laravel.content.allowed_statuses', ['draft', 'published'])),
            'banner_image' => 'nullable|url',
            'banner_thumbnail' => 'nullable|url',
            'banner_medium' => 'nullable|url',
            'banner_large' => 'nullable|url',
            'banner_original' => 'nullable|url',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Title is required',
            'title.max' => 'Title must not exceed 255 characters',
            'content.required' => 'Content is required',
            'slug.regex' => 'Slug must contain only lowercase letters, numbers, and hyphens',
            'slug.unique' => 'This slug is already in use',
            'meta_title.max' => 'Meta title must not exceed 255 characters',
            'meta_description.max' => 'Meta description must not exceed 500 characters',
            'focus_keyword.max' => 'Focus keyword must not exceed 100 characters',
            'canonical_url.url' => 'Canonical URL must be a valid URL',
            'canonical_url.max' => 'Canonical URL must not exceed 500 characters',
            'published_at.date' => 'Published date must be a valid date',
            'custom_fields.array' => 'Custom fields must be an array',
            'status.in' => 'Status must be one of: '.implode(', ', config('articlai-laravel.content.allowed_statuses', ['draft', 'published'])),
            'banner_image.url' => 'Banner image must be a valid URL',
            'banner_thumbnail.url' => 'Banner thumbnail must be a valid URL',
            'banner_medium.url' => 'Banner medium must be a valid URL',
            'banner_large.url' => 'Banner large must be a valid URL',
            'banner_original.url' => 'Banner original must be a valid URL',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw ArticlaiException::validationFailed($validator->errors()->toArray());
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default status if not provided
        if (! $this->has('status')) {
            $this->merge([
                'status' => config('articlai-laravel.content.default_status', 'published'),
            ]);
        }

        // Set published_at to current time if status is published and no date provided
        if ($this->input('status') === 'published' && ! $this->has('published_at')) {
            $this->merge([
                'published_at' => now()->toISOString(),
            ]);
        }

        // Ensure custom_fields is an array if provided as JSON string
        if ($this->has('custom_fields') && is_string($this->input('custom_fields'))) {
            try {
                $customFields = json_decode($this->input('custom_fields'), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->merge(['custom_fields' => $customFields]);
                }
            } catch (\Exception $e) {
                // Let validation handle the error
            }
        }
    }
}
