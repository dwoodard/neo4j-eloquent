<?php

namespace Neo4jEloquent;

class Node
{
    protected array $attributes = [];

    protected array $labels = [];

    protected ?string $id = null;

    protected bool $exists = false;

    protected static ?Neo4jService $neo4jService = null;

    /**
     * Create a new Node instance.
     */
    public function __construct(array $attributes = [], array $labels = [])
    {
        $this->attributes = $attributes;
        $this->labels = $labels;

        if (isset($attributes['id'])) {
            $this->id = $attributes['id'];
            $this->exists = true;
        }
    }

    /**
     * Static factory method to create a labeled node.
     */
    public static function label(string ...$labels): NodeBuilder
    {
        return new NodeBuilder($labels);
    }

    /**
     * Set the Neo4j service instance.
     */
    public static function setNeo4jService(Neo4jService $service): void
    {
        static::$neo4jService = $service;
    }

    /**
     * Get the Neo4j service instance.
     */
    public static function getNeo4jService(): Neo4jService
    {
        if (static::$neo4jService === null) {
            throw new \RuntimeException('Neo4j service not set. Make sure the service provider is registered.');
        }

        return static::$neo4jService;
    }

    /**
     * Get the node's ID.
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Set the node's ID.
     */
    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the node's labels.
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    /**
     * Add a label to the node.
     */
    public function addLabel(string $label): self
    {
        if (! in_array($label, $this->labels)) {
            $this->labels[] = $label;
        }

        return $this;
    }

    /**
     * Get all attributes.
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Get a specific attribute.
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Set an attribute.
     */
    public function setAttribute(string $key, mixed $value): self
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Check if the node exists in the database.
     */
    public function exists(): bool
    {
        return $this->exists;
    }

    /**
     * Mark the node as existing.
     */
    public function setExists(bool $exists): self
    {
        $this->exists = $exists;

        return $this;
    }

    /**
     * Convert the node to an array.
     */
    public function toArray(): array
    {
        $array = $this->attributes;

        if ($this->id !== null) {
            $array['id'] = $this->id;
        }

        $array['labels'] = $this->labels;

        return $array;
    }

    /**
     * Magic getter for attributes.
     */
    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    /**
     * Magic setter for attributes.
     */
    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Magic isset for attributes.
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Magic unset for attributes.
     */
    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }

    /**
     * Save the node to the database.
     */
    public function save(): bool
    {
        $service = static::getNeo4jService();

        if ($this->exists()) {
            return $this->performUpdate($service);
        } else {
            return $this->performInsert($service);
        }
    }

    /**
     * Perform an insert operation.
     */
    protected function performInsert(Neo4jService $service): bool
    {
        // Generate ID if not set and auto UUID is enabled
        if ($this->id === null && ($service->getConfig()['auto_uuid'] ?? true)) {
            $this->id = $service->generateUuid();
        }

        $labels = $this->buildLabelsString();
        $properties = $this->preparePropertiesForCypher();

        $cypher = "CREATE (n{$labels} {$properties}) RETURN n";

        $parameters = $this->attributes;
        if ($this->id !== null) {
            $parameters['id'] = $this->id;
        }

        $result = $service->run($cypher, $parameters);

        if ($result->count() > 0) {
            $this->exists = true;

            return true;
        }

        return false;
    }

    /**
     * Perform an update operation.
     */
    protected function performUpdate(Neo4jService $service): bool
    {
        if ($this->id === null) {
            throw new \RuntimeException('Cannot update node without an ID');
        }

        $setClause = $this->buildSetClause();

        $cypher = "MATCH (n) WHERE n.id = \$id SET {$setClause} RETURN n";
        $parameters = array_merge($this->attributes, ['id' => $this->id]);

        $result = $service->run($cypher, $parameters);

        return $result->count() > 0;
    }

    /**
     * Build the labels string for Cypher.
     */
    protected function buildLabelsString(): string
    {
        if (empty($this->labels)) {
            return '';
        }

        return ':'.implode(':', $this->labels);
    }

    /**
     * Prepare properties for Cypher query.
     */
    protected function preparePropertiesForCypher(): string
    {
        $properties = [];

        foreach ($this->attributes as $key => $value) {
            $properties[] = "{$key}: \${$key}";
        }

        if ($this->id !== null) {
            $properties[] = 'id: $id';
        }

        return '{'.implode(', ', $properties).'}';
    }

    /**
     * Build SET clause for updates.
     */
    protected function buildSetClause(): string
    {
        $setClauses = [];

        foreach ($this->attributes as $key => $value) {
            $setClauses[] = "n.{$key} = \${$key}";
        }

        return implode(', ', $setClauses);
    }

    /**
     * Delete the node from the database.
     */
    public function delete(): bool
    {
        if (! $this->exists() || $this->id === null) {
            return false;
        }

        $service = static::getNeo4jService();

        $cypher = 'MATCH (n) WHERE n.id = $id DELETE n';
        $result = $service->run($cypher, ['id' => $this->id]);

        $this->exists = false;

        return true;
    }
}
