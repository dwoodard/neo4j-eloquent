<?php

namespace Neo4jEloquent;

use Laudis\Neo4j\Authentication\Authenticate;
use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Types\CypherList;

class Neo4jService
{
    protected ClientInterface $client;

    protected array $config;

    protected string $defaultConnection;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->defaultConnection = $config['default'] ?? 'default';
        $this->client = $this->createClient();
    }

    /**
     * Create the Neo4j client instance.
     */
    protected function createClient(): ClientInterface
    {
        $connectionConfig = $this->config['connections'][$this->defaultConnection];

        $builder = ClientBuilder::create();

        $uri = $this->buildUri($connectionConfig);

        if (isset($connectionConfig['username']) && isset($connectionConfig['password'])) {
            $auth = Authenticate::basic(
                $connectionConfig['username'],
                $connectionConfig['password']
            );
            $builder->withDriver($connectionConfig['driver'], $uri, $auth);
        } else {
            $builder->withDriver($connectionConfig['driver'], $uri);
        }

        return $builder->build();
    }

    /**
     * Build the connection URI.
     */
    protected function buildUri(array $config): string
    {
        $scheme = $config['driver'] === 'bolt' ? 'bolt' : 'http';
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? ($scheme === 'bolt' ? 7687 : 7474);

        return "{$scheme}://{$host}:{$port}";
    }

    /**
     * Run a Cypher query.
     */
    public function run(string $cypher, array $parameters = []): CypherList
    {
        if ($this->shouldLogQueries()) {
            $this->logQuery($cypher, $parameters);
        }

        return $this->client->run($cypher, $parameters);
    }

    /**
     * Run a raw Cypher query and return results.
     */
    public function runRaw(string $cypher, array $parameters = []): CypherList
    {
        return $this->run($cypher, $parameters);
    }

    /**
     * Run multiple statements in a transaction.
     */
    public function transaction(callable $callback): mixed
    {
        return $this->client->writeTransaction($callback);
    }

    /**
     * Get the underlying client instance.
     */
    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * Get the configuration.
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Check if query logging is enabled.
     */
    protected function shouldLogQueries(): bool
    {
        return $this->config['logging']['enabled'] ?? false;
    }

    /**
     * Log a query for debugging.
     */
    protected function logQuery(string $cypher, array $parameters = []): void
    {
        if (function_exists('logger')) {
            logger()->channel($this->config['logging']['channel'] ?? 'default')->info('Neo4j Query', [
                'cypher' => $cypher,
                'parameters' => $parameters,
                'connection' => $this->defaultConnection,
            ]);
        }
    }

    /**
     * Generate a UUID.
     */
    public function generateUuid(): string
    {
        if (function_exists('str')) {
            return (string) str()->uuid();
        }

        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0x0FFF) | 0x4000,
            mt_rand(0, 0x3FFF) | 0x8000,
            mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF)
        );
    }
}
