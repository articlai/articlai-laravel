<?php

namespace Articlai\Articlai\Tests;

use Articlai\Articlai\Contracts\ArticlaiConnectable;
use Articlai\Articlai\Services\ModelResolver;
use Articlai\Articlai\Traits\ArticlaiConnector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConnectorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test table for our custom model
        $this->app['db']->connection()->getSchemaBuilder()->create('test_posts', function ($table) {
            $table->id();
            $table->string('post_title');
            $table->text('post_body');
            $table->string('post_slug')->unique();
            $table->string('post_status')->default('draft');
            $table->timestamp('publish_date')->nullable();
            $table->timestamps();
        });
    }

    /** @test */
    public function it_can_use_custom_model_with_connector()
    {
        // Configure to use our test model
        config(['articlai-laravel.model.class' => TestPost::class]);
        
        $resolver = new ModelResolver();
        
        $data = [
            'title' => 'Test Post',
            'content' => 'This is test content',
            'slug' => 'test-post',
            'status' => 'published',
        ];
        
        $post = $resolver->create($data);
        
        $this->assertInstanceOf(TestPost::class, $post);
        $this->assertEquals('Test Post', $post->post_title);
        $this->assertEquals('This is test content', $post->post_body);
        $this->assertEquals('test-post', $post->post_slug);
        $this->assertEquals('published', $post->post_status);
    }

    /** @test */
    public function it_can_retrieve_articlai_data_with_field_mapping()
    {
        config(['articlai-laravel.model.class' => TestPost::class]);
        
        $post = TestPost::create([
            'post_title' => 'Test Post',
            'post_body' => 'This is test content',
            'post_slug' => 'test-post',
            'post_status' => 'published',
        ]);
        
        $articlaiData = $post->getArticlaiData();
        
        $this->assertEquals('Test Post', $articlaiData['title']);
        $this->assertEquals('This is test content', $articlaiData['content']);
        $this->assertEquals('test-post', $articlaiData['slug']);
        $this->assertEquals('published', $articlaiData['status']);
        $this->assertTrue($articlaiData['is_published']);
    }

    /** @test */
    public function it_can_set_articlai_data_with_field_mapping()
    {
        $post = new TestPost();
        
        $data = [
            'title' => 'New Post',
            'content' => 'New content',
            'slug' => 'new-post',
            'status' => 'draft',
        ];
        
        $post->setArticlaiData($data);
        
        $this->assertEquals('New Post', $post->post_title);
        $this->assertEquals('New content', $post->post_body);
        $this->assertEquals('new-post', $post->post_slug);
        $this->assertEquals('draft', $post->post_status);
    }

    /** @test */
    public function it_can_generate_unique_slugs()
    {
        config(['articlai-laravel.model.class' => TestPost::class]);
        
        // Create a post with a specific slug
        TestPost::create([
            'post_title' => 'Test Post',
            'post_body' => 'Content',
            'post_slug' => 'test-post',
            'post_status' => 'published',
        ]);
        
        $resolver = new ModelResolver();
        
        // Try to create another post with the same title
        $post = $resolver->create([
            'title' => 'Test Post',
            'content' => 'Different content',
            'status' => 'published',
        ]);
        
        $this->assertEquals('test-post-1', $post->post_slug);
    }

    /** @test */
    public function it_can_check_published_status()
    {
        $publishedPost = new TestPost([
            'post_status' => 'published',
            'publish_date' => now()->subDay(),
        ]);
        
        $draftPost = new TestPost([
            'post_status' => 'draft',
        ]);
        
        $futurePost = new TestPost([
            'post_status' => 'published',
            'publish_date' => now()->addDay(),
        ]);
        
        $this->assertTrue($publishedPost->isPublished());
        $this->assertFalse($draftPost->isPublished());
        $this->assertFalse($futurePost->isPublished());
    }

    /** @test */
    public function it_can_use_scopes()
    {
        TestPost::create([
            'post_title' => 'Published Post',
            'post_body' => 'Content',
            'post_slug' => 'published-post',
            'post_status' => 'published',
        ]);
        
        TestPost::create([
            'post_title' => 'Draft Post',
            'post_body' => 'Content',
            'post_slug' => 'draft-post',
            'post_status' => 'draft',
        ]);
        
        $publishedPosts = TestPost::published()->get();
        $draftPosts = TestPost::byStatus('draft')->get();
        
        $this->assertCount(1, $publishedPosts);
        $this->assertCount(1, $draftPosts);
        $this->assertEquals('Published Post', $publishedPosts->first()->post_title);
        $this->assertEquals('Draft Post', $draftPosts->first()->post_title);
    }
}

/**
 * Test model for connector testing
 */
class TestPost extends Model implements ArticlaiConnectable
{
    use ArticlaiConnector;
    
    protected $table = 'test_posts';
    
    protected $fillable = [
        'post_title',
        'post_body',
        'post_slug',
        'post_status',
        'publish_date',
    ];
    
    protected $casts = [
        'publish_date' => 'datetime',
    ];
    
    protected $articlaiFieldMapping = [
        'title' => 'post_title',
        'content' => 'post_body',
        'slug' => 'post_slug',
        'status' => 'post_status',
        'published_at' => 'publish_date',
    ];
}
