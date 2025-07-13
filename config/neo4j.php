<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Neo4j Connection Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Neo4j database connections. You can configure multiple
    | connections and specify which one to use as the default.
    |
    */

    'default' => env('NEO4J_CONNECTION', 'default'),

    'connections' => [
        'default' => [
            'driver' => 'bolt',
            'host' => env('NEO4J_HOST', 'localhost'),
            'port' => env('NEO4J_PORT', 7687),
            'username' => env('NEO4J_USERNAME', 'neo4j'),
            'password' => env('NEO4J_PASSWORD', 'password'),
            'database' => env('NEO4J_DATABASE', 'neo4j'),
            'timeout' => env('NEO4J_TIMEOUT', 5),
        ],

        'http' => [
            'driver' => 'http',
            'host' => env('NEO4J_HTTP_HOST', 'localhost'),
            'port' => env('NEO4J_HTTP_PORT', 7474),
            'username' => env('NEO4J_USERNAME', 'neo4j'),
            'password' => env('NEO4J_PASSWORD', 'password'),
            'database' => env('NEO4J_DATABASE', 'neo4j'),
            'timeout' => env('NEO4J_TIMEOUT', 5),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Logging
    |--------------------------------------------------------------------------
    |
    | Enable query logging for debugging purposes. When enabled, all Cypher
    | queries will be logged to the configured log channel.
    |
    */

    'logging' => [
        'enabled' => env('NEO4J_LOG_QUERIES', false),
        'channel' => env('NEO4J_LOG_CHANNEL', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto UUID Generation
    |--------------------------------------------------------------------------
    |
    | Automatically generate UUIDs for nodes that don't have an ID specified
    | when creating new nodes.
    |
    */

    'auto_uuid' => env('NEO4J_AUTO_UUID', true),

    /*
    |--------------------------------------------------------------------------
    | Default Labels
    |--------------------------------------------------------------------------
    |
    | Default label configuration for nodes and relationships.
    |
    */

    'defaults' => [
        'node_label' => 'Node',
        'created_at_field' => 'created_at',
        'updated_at_field' => 'updated_at',
    ],
];
