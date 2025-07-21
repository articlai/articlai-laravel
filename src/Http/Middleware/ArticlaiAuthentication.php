<?php

namespace Articlai\Articlai\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Articlai\Articlai\Exceptions\ArticlaiException;

class ArticlaiAuthentication
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authMethod = config('articlai-laravel.auth.method', 'api_key');

        try {
            switch ($authMethod) {
                case 'api_key':
                    $this->validateApiKey($request);
                    break;
                case 'bearer_token':
                    $this->validateBearerToken($request);
                    break;
                case 'basic_auth':
                    $this->validateBasicAuth($request);
                    break;
                default:
                    throw ArticlaiException::authenticationFailed('Invalid authentication method configured');
            }
        } catch (ArticlaiException $e) {
            return $e->render();
        }

        return $next($request);
    }

    /**
     * Validate API key authentication
     */
    protected function validateApiKey(Request $request): void
    {
        $apiKey = $request->header('X-API-Key');
        $expectedApiKey = config('articlai-laravel.auth.api_key');

        if (empty($expectedApiKey)) {
            throw ArticlaiException::authenticationFailed('API key not configured. Please set ARTICLAI_API_KEY in your environment or publish the config file.');
        }

        if (empty($apiKey)) {
            throw ArticlaiException::authenticationFailed('X-API-Key header is required');
        }

        if (!hash_equals($expectedApiKey, $apiKey)) {
            throw ArticlaiException::authenticationFailed('Invalid API key');
        }
    }


}
