<?php

namespace Articlai\Articlai\Contracts;

interface ArticlaiConnectable
{
    /**
     * Get data formatted for Articlai API responses
     */
    public function getArticlaiData(): array;

    /**
     * Set data from Articlai API requests with field mapping
     */
    public function setArticlaiData(array $data): void;

    /**
     * Generate a unique slug from the title
     */
    public function generateUniqueSlug(string $title): string;

    /**
     * Check if the post is published
     */
    public function isPublished(): bool;

    /**
     * Get the URL for this post
     */
    public function getUrl(): string;

    /**
     * Scope to filter published posts
     */
    public function scopePublished($query);

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, string $status);

    /**
     * Get the field mapping configuration for this model
     */
    public function getArticlaiFieldMapping(): array;

    /**
     * Add banner image from URL (optional - only if media support is enabled)
     */
    public function addBannerFromUrl(string $url): void;

    /**
     * Get banner image URL (optional - only if media support is enabled)
     */
    public function getBannerImage(): ?string;
}
