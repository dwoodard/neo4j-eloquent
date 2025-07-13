<?php

namespace Neo4jEloquent\Laravel;

use Illuminate\Support\ServiceProvider;
use Neo4jEloquent\Neo4jService;
use Neo4jEloquent\Node;

class Neo4jEloquentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge default config
        $this->app['config']->set('neo4j', array_merge([
            'connections' => [
                'default' => [
                    'uri' => env('NEO4J_URI', 'bolt://localhost:7687'),
                    'username' => env('NEO4J_USERNAME', 'neo4j'),
                    'password' => env('NEO4J_PASSWORD', 'password'),
                    'database' => env('NEO4J_DATABASE', 'neo4j'),
                ],
            ],
            'default' => env('NEO4J_CONNECTION', 'default'),
            'log_queries' => env('NEO4J_LOG_QUERIES', false),
            'auto_uuid' => env('NEO4J_AUTO_UUID', true),
        ], $this->app['config']->get('neo4j', [])));

        $this->app->singleton(Neo4jService::class, function ($app) {
            return new Neo4jService($app['config']['neo4j']);
        });

        $this->app->alias(Neo4jService::class, 'neo4j');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config file if running in console
        if ($this->app->runningInConsole()) {
            $configPath = __DIR__.'/../../config/neo4j.php';
            if (file_exists($configPath)) {
                $this->publishes([
                    $configPath => config_path('neo4j.php'),
                ], 'neo4j-config');
            }
        }

        // FIXED: Set the Neo4j service on the Node class
        $this->app->booted(function () {
            if ($this->app->has(Neo4jService::class)) {
                Node::setNeo4jService($this->app->make(Neo4jService::class));
            }
        });
    }
}
