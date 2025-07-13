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
        $this->mergeConfigFrom(
            __DIR__.'/../../../config/neo4j.php',
            'neo4j'
        );

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
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../../config/neo4j.php' => config_path('neo4j.php'),
            ], 'neo4j-config');
        }

        // FIXED: Set the Neo4j service on the Node class
        $this->app->booted(function () {
            if ($this->app->has(Neo4jService::class)) {
                Node::setNeo4jService($this->app->make(Neo4jService::class));
            }
        });
    }
}
