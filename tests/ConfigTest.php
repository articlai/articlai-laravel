<?php

namespace Articlai\Articlai\Tests;

class ConfigTest extends TestCase
{
    /** @test */
    public function it_loads_config_values()
    {
        // Test that config values are accessible
        $this->assertEquals('api_key', config('articlai-laravel.auth.method'));
        $this->assertEquals('test-api-key', config('articlai-laravel.auth.api_key'));
        $this->assertEquals('Test Platform', config('articlai-laravel.platform.name'));
    }

    /** @test */
    public function it_has_default_values()
    {
        // Test default values when environment variables are not set
        $this->assertEquals('api/articlai', config('articlai-laravel.api.prefix'));
        $this->assertEquals(['api', 'articlai.auth'], config('articlai-laravel.api.middleware'));
        $this->assertEquals('published', config('articlai-laravel.content.default_status'));
    }

    /** @test */
    public function it_can_override_config_values()
    {
        // Test that we can override config values
        config()->set('articlai-laravel.auth.api_key', 'new-test-key');
        $this->assertEquals('new-test-key', config('articlai-laravel.auth.api_key'));
    }

    /** @test */
    public function it_registers_config_for_publishing()
    {
        // Test that the config file is registered for publishing
        $publishGroups = \Illuminate\Support\ServiceProvider::$publishGroups;

        $this->assertArrayHasKey('articlai-laravel-config', $publishGroups);

        $configFiles = $publishGroups['articlai-laravel-config'];
        $this->assertNotEmpty($configFiles);

        // Check that the source file exists
        $sourceFile = array_keys($configFiles)[0];
        $this->assertFileExists($sourceFile);
        $this->assertStringContainsString('config/articlai-laravel.php', $sourceFile);
    }
}
