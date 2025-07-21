<?php

namespace Articlai\Articlai\Tests;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Articlai\Articlai\Http\Middleware\ArticlaiAuthentication;
use Articlai\Articlai\Exceptions\ArticlaiException;

class MiddlewareTest extends TestCase
{
    /** @test */
    public function it_allows_request_with_valid_api_key()
    {
        $middleware = new ArticlaiAuthentication();
        $request = Request::create('/test', 'GET');
        $request->headers->set('X-API-Key', 'test-api-key');

        $response = $middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_rejects_request_with_invalid_api_key()
    {
        $middleware = new ArticlaiAuthentication();
        $request = Request::create('/test', 'GET');
        $request->headers->set('X-API-Key', 'invalid-key');

        $response = $middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    /** @test */
    public function it_rejects_request_without_api_key()
    {
        $middleware = new ArticlaiAuthentication();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    /** @test */
    public function it_handles_missing_config()
    {
        // Temporarily remove the API key config
        config()->set('articlai-laravel.auth.api_key', null);

        $middleware = new ArticlaiAuthentication();
        $request = Request::create('/test', 'GET');
        $request->headers->set('X-API-Key', 'some-key');

        $response = $middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
        
        // Restore the config for other tests
        config()->set('articlai-laravel.auth.api_key', 'test-api-key');
    }
}
