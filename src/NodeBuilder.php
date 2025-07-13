<?php

namespace Neo4jEloquent;

use Illuminate\Support\Collection;

class NodeBuilder
{
    protected array $labels = [];
    protected array $wheres = [];
    protected array $parameters = [];
    protected ?int $limit = null;
    protected ?string $orderBy = null;
    protected ?string $orderDirection = 'asc';
    protected int $parameterCounter = 0;

    public function __construct(array $labels = [])
    {
        $this->labels = $labels;
    }

    /**
     * Add a where condition.
     */
    public function where(string $field, string $operator = '=', mixed $value = null): self
    {
        // If only 2 arguments provided, assume '=' operator
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $paramName = $this->getNextParameterName();
        $this->parameters[$paramName] = $value;

        $this->wheres[] = "n.{$field} {$operator} \${$paramName}";

        return $this;
    }

    /**
     * Add an OR where condition.
     */
    public function orWhere(string $field, string $operator = '=', mixed $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $paramName = $this->getNextParameterName();
        $this->parameters[$paramName] = $value;

        $this->wheres[] = "OR n.{$field} {$operator} \${$paramName}";

        return $this;
    }

    /**
     * Add a where in condition.
     */
    public function whereIn(string $field, array $values): self
    {
        $paramName = $this->getNextParameterName();
        $this->parameters[$paramName] = $values;

        $this->wheres[] = "n.{$field} IN \${$paramName}";

        return $this;
    }

    /**
     * Add a limit to the query.
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Add ordering to the query.
     */
    public function orderBy(string $field, string $direction = 'asc'): self
    {
        $this->orderBy = $field;
        $this->orderDirection = strtolower($direction);
        return $this;
    }

    /**
     * Get the first result.
     */
    public function first(): ?Node
    {
        $results = $this->limit(1)->get();
        return $results->first();
    }

    /**
     * Find a node by ID.
     */
    public function find(string $id): ?Node
    {
        return $this->where('id', $id)->first();
    }

    /**
     * Execute the query and get results.
     */
    public function get(): Collection
    {
        $cypher = $this->buildSelectCypher();
        $service = Node::getNeo4jService();
        
        $result = $service->run($cypher, $this->parameters);
        
        $nodes = new Collection();
        
        foreach ($result as $record) {
            $nodeData = $record->get('n');
            $node = $this->hydrateNode($nodeData);
            $nodes->push($node);
        }
        
        return $nodes;
    }

    /**
     * Create a new node with the specified attributes.
     */
    public function create(array $attributes = []): Node
    {
        $node = new Node($attributes, $this->labels);
        $node->save();
        
        return $node;
    }

    /**
     * Update nodes matching the query.
     */
    public function update(array $attributes): int
    {
        if (empty($this->wheres)) {
            throw new \InvalidArgumentException('Cannot update without where conditions for safety');
        }

        $setClauses = [];
        foreach ($attributes as $field => $value) {
            $paramName = $this->getNextParameterName();
            $this->parameters[$paramName] = $value;
            $setClauses[] = "n.{$field} = \${$paramName}";
        }

        $cypher = $this->buildMatchClause() . 
                  $this->buildWhereClause() . 
                  ' SET ' . implode(', ', $setClauses) .
                  ' RETURN count(n) as updated';

        $service = Node::getNeo4jService();
        $result = $service->run($cypher, $this->parameters);
        
        return $result->first()->get('updated');
    }

    /**
     * Delete nodes matching the query.
     */
    public function delete(): int
    {
        if (empty($this->wheres)) {
            throw new \InvalidArgumentException('Cannot delete without where conditions for safety');
        }

        $cypher = $this->buildMatchClause() . 
                  $this->buildWhereClause() . 
                  ' DELETE n RETURN count(n) as deleted';

        $service = Node::getNeo4jService();
        $result = $service->run($cypher, $this->parameters);
        
        return $result->first()->get('deleted');
    }

    /**
     * Count the number of matching nodes.
     */
    public function count(): int
    {
        $cypher = $this->buildMatchClause() . 
                  $this->buildWhereClause() . 
                  ' RETURN count(n) as total';

        $service = Node::getNeo4jService();
        $result = $service->run($cypher, $this->parameters);
        
        return $result->first()->get('total');
    }

    /**
     * Build the complete SELECT Cypher query.
     */
    protected function buildSelectCypher(): string
    {
        $cypher = $this->buildMatchClause();
        $cypher .= $this->buildWhereClause();
        $cypher .= ' RETURN n';
        $cypher .= $this->buildOrderClause();
        $cypher .= $this->buildLimitClause();

        return $cypher;
    }

    /**
     * Build the MATCH clause.
     */
    protected function buildMatchClause(): string
    {
        $labels = empty($this->labels) ? '' : ':' . implode(':', $this->labels);
        return "MATCH (n{$labels})";
    }

    /**
     * Build the WHERE clause.
     */
    protected function buildWhereClause(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        return ' WHERE ' . implode(' AND ', $this->wheres);
    }

    /**
     * Build the ORDER BY clause.
     */
    protected function buildOrderClause(): string
    {
        if ($this->orderBy === null) {
            return '';
        }

        return " ORDER BY n.{$this->orderBy} " . strtoupper($this->orderDirection);
    }

    /**
     * Build the LIMIT clause.
     */
    protected function buildLimitClause(): string
    {
        if ($this->limit === null) {
            return '';
        }

        return " LIMIT {$this->limit}";
    }

    /**
     * Hydrate a Node instance from Neo4j data.
     */
    protected function hydrateNode($nodeData): Node
    {
        $properties = $nodeData->getProperties();
        $labels = $nodeData->getLabels()->toArray();
        
        $node = new Node($properties, $labels);
        
        if (isset($properties['id'])) {
            $node->setId($properties['id']);
        }
        
        $node->setExists(true);
        
        return $node;
    }

    /**
     * Get the next parameter name.
     */
    protected function getNextParameterName(): string
    {
        return 'param' . (++$this->parameterCounter);
    }

    /**
     * Start a relationship traversal.
     */
    public function outgoing(string $relationshipType): RelationshipBuilder
    {
        return new RelationshipBuilder($this, 'outgoing', $relationshipType);
    }

    /**
     * Start an incoming relationship traversal.
     */
    public function incoming(string $relationshipType): RelationshipBuilder
    {
        return new RelationshipBuilder($this, 'incoming', $relationshipType);
    }

    /**
     * Start a bidirectional relationship traversal.
     */
    public function related(string $relationshipType): RelationshipBuilder
    {
        return new RelationshipBuilder($this, 'both', $relationshipType);
    }

    /**
     * Get the current query parameters.
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Get the current where conditions.
     */
    public function getWheres(): array
    {
        return $this->wheres;
    }

    /**
     * Get the current labels.
     */
    public function getLabels(): array
    {
        return $this->labels;
    }
}
