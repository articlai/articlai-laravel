<?php

namespace Articlai\Articlai\Commands;

use Articlai\Articlai\Models\ArticlaiPost;
use Illuminate\Console\Command;

class ArticlaiCommand extends Command
{
    public $signature = 'articlai:status {--posts=10 : Number of recent posts to show} {--debug : Show detailed debug information}';

    public $description = 'Show ArticlAI package status and recent posts';

    public function handle(): int
    {
        $this->info('ArticlAI Laravel Package Status');
        $this->line('================================');

        // Show configuration status
        $this->showConfigurationStatus();

        // Show debug information if requested
        if ($this->option('debug')) {
            $this->showDebugInformation();
        }

        // Show recent posts
        $postsCount = (int) $this->option('posts');
        $this->showRecentPosts($postsCount);

        return self::SUCCESS;
    }

    protected function showConfigurationStatus(): void
    {
        $this->line('');
        $this->info('Configuration:');

        $authMethod = config('articlai-laravel.auth.method', 'not configured');
        $this->line("  Auth Method: {$authMethod}");

        $apiKey = config('articlai-laravel.auth.api_key') ? 'configured' : 'not configured';
        $this->line("  API Key: {$apiKey}");

        $platformName = config('articlai-laravel.platform.name', 'Laravel Application');
        $this->line("  Platform: {$platformName}");

        $sanitizeHtml = config('articlai-laravel.content.sanitize_html', true) ? 'enabled' : 'disabled';
        $this->line("  HTML Sanitization: {$sanitizeHtml}");
    }

    protected function showRecentPosts(int $count): void
    {
        $this->line('');
        $this->info("Recent Posts (last {$count}):");

        $posts = ArticlaiPost::orderBy('created_at', 'desc')
            ->limit($count)
            ->get(['id', 'title', 'status', 'created_at']);

        if ($posts->isEmpty()) {
            $this->line('  No posts found');

            return;
        }

        $headers = ['ID', 'Title', 'Status', 'Created'];
        $rows = $posts->map(function ($post) {
            return [
                $post->id,
                \Illuminate\Support\Str::limit($post->title, 40),
                $post->status,
                $post->created_at->format('Y-m-d H:i'),
            ];
        })->toArray();

        $this->table($headers, $rows);

        $totalPosts = ArticlaiPost::count();
        $this->line("Total posts in database: {$totalPosts}");
    }

    protected function showDebugInformation(): void
    {
        $this->line('');
        $this->info('Debug Information:');

        // Show all config values
        $config = config('articlai-laravel');
        $this->line('  Full Configuration:');
        $this->line('    '.json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Show environment variables
        $this->line('');
        $this->line('  Environment Variables:');
        $envVars = [
            'ARTICLAI_AUTH_METHOD',
            'ARTICLAI_API_KEY',
            'ARTICLAI_BEARER_TOKEN',
            'ARTICLAI_BASIC_USERNAME',
            'ARTICLAI_BASIC_PASSWORD',
            'ARTICLAI_PLATFORM_NAME',
            'ARTICLAI_PLATFORM_VERSION',
        ];

        foreach ($envVars as $var) {
            $value = env($var);
            $display = $value ? (strlen($value) > 20 ? substr($value, 0, 10).'...' : $value) : 'not set';
            $this->line("    {$var}: {$display}");
        }

        // Show Laravel version and environment
        $this->line('');
        $this->line('  Laravel Information:');
        $this->line('    Laravel Version: '.app()->version());
        $this->line('    Environment: '.app()->environment());
        $this->line('    Config Cached: '.(app()->configurationIsCached() ? 'yes' : 'no'));
    }
}
