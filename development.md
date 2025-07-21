# Package Development Guide

This guide provides instructions for creating reusable packages and integrations for ArticlAI's Custom API connection type. Whether you're building a CMS plugin, SDK, or integration library, this guide will help you create robust solutions.

## Overview

ArticlAI's Custom API connection type enables integration with any system that can expose HTTP API endpoints. This guide covers best practices for creating packages that work seamlessly with ArticlAI.

## Package Types

### 1. CMS Plugins
- WordPress plugins
- Drupal modules  
- Joomla extensions
- Custom CMS integrations

### 2. SDK Libraries
- PHP SDK for Laravel/Symfony
- Node.js SDK for Express/Next.js
- Python SDK for Django/Flask
- Ruby SDK for Rails

### 3. Platform Integrations
- Headless CMS connectors (Contentful, Strapi)
- E-commerce platform plugins (Shopify, WooCommerce)
- Static site generators (Gatsby, Next.js)

## Core Requirements

### API Endpoints

Your package must implement these endpoints:

#### 1. Validation Endpoint (Optional but Recommended)
```
GET /api/articlai/validate
```

**Response:**
```json
{
  "success": true,
  "message": "Connection validated successfully",
  "platform_info": {
    "name": "Your Platform",
    "version": "1.0.0",
    "capabilities": ["create", "update", "delete"]
  }
}
```

#### 2. Create Content Endpoint
```
POST /api/articlai/posts
```

**Request Body:**
```json
{
  "title": "Blog Post Title",
  "content": "<p>HTML content or markdown</p>",
  "excerpt": "Post excerpt",
  "slug": "blog-post-title",
  "meta_title": "SEO Title",
  "meta_description": "SEO Description",
  "focus_keyword": "keyword",
  "canonical_url": "https://example.com/canonical",
  "published_at": "2024-01-01T12:00:00Z",
  "custom_fields": {
    "author": "ArticlAI",
    "source": "automated"
  }
}
```

**Response:**
```json
{
  "id": "123",
  "url": "https://yoursite.com/blog/blog-post-title",
  "title": "Blog Post Title",
  "status": "published",
  "created_at": "2024-01-01T12:00:00Z"
}
```

#### 3. Update Content Endpoint
```
PUT /api/articlai/posts/{id}
```

Same request/response format as create, but updates existing content.

#### 4. Delete Content Endpoint (Optional)
```
DELETE /api/articlai/posts/{id}
```

**Response:**
```json
{
  "success": true,
  "message": "Post deleted successfully"
}
```

### Authentication

Implement secure authentication using one of these methods:

#### API Key Header
```
X-API-Key: your-secret-api-key
```

#### Bearer Token
```
Authorization: Bearer your-access-token
```

#### Basic Authentication
```
Authorization: Basic base64(username:password)
```

## Implementation Guidelines

### 1. Input Validation

Always validate and sanitize incoming data:

```php
// PHP Example
function validate_post_data($data) {
    $errors = [];
    
    if (empty($data['title'])) {
        $errors[] = 'Title is required';
    }
    
    if (empty($data['content'])) {
        $errors[] = 'Content is required';
    }
    
    if (!empty($data['slug']) && !preg_match('/^[a-z0-9-]+$/', $data['slug'])) {
        $errors[] = 'Invalid slug format';
    }
    
    return $errors;
}
```

### 2. Error Handling

Provide clear, actionable error messages:

```json
{
  "error": "Validation failed",
  "details": {
    "title": ["Title is required"],
    "slug": ["Slug must contain only lowercase letters, numbers, and hyphens"]
  },
  "code": "VALIDATION_ERROR"
}
```

### 3. Content Processing

Handle different content formats appropriately:

```javascript
// Node.js Example
function processContent(content, format = 'html') {
  if (format === 'markdown') {
    return markdownToHtml(content);
  }
  
  // Sanitize HTML content
  return sanitizeHtml(content, {
    allowedTags: ['p', 'br', 'strong', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'a', 'img'],
    allowedAttributes: {
      'a': ['href', 'title'],
      'img': ['src', 'alt', 'title']
    }
  });
}
```

### 4. Metadata Handling

Process SEO and custom metadata:


## Configuration Templates


### Headless CMS Configuration


### Custom API Configuration

```json
{
  "auth_type": "basic",
  "content_format": "html",
  "publish_endpoint": "/api/v1/blog/posts",
  "update_endpoint": "/api/v1/blog/posts/{id}",
  "delete_endpoint": "/api/v1/blog/posts/{id}",
  "validation_endpoint": "/api/v1/auth/check",
  "methods": {
    "publish": "POST",
    "update": "PATCH",
    "delete": "DELETE"
  },
  "payload_template": {
    "publish": {
      "post": {
        "title": "{title}",
        "content": "{content}",
        "summary": "{excerpt}",
        "status": "published",
        "metadata": {
          "seo_title": "{meta_title}",
          "seo_description": "{meta_description}",
          "canonical": "{canonical_url}"
        }
      }
    }
  }
}
```

## Testing Your Package

### 1. Unit Tests

Test individual components:

```php
// PHPUnit Example
class ArticlAIIntegrationTest extends TestCase {
    
    public function testCreatePost() {
        $data = [
            'title' => 'Test Post',
            'content' => '<p>Test content</p>',
            'excerpt' => 'Test excerpt'
        ];
        
        $result = $this->integration->createPost($data);
        
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertEquals('Test Post', $result['title']);
    }
    
    public function testValidation() {
        $result = $this->integration->validateConnection();
        
        $this->assertTrue($result['success']);
    }
}
```
