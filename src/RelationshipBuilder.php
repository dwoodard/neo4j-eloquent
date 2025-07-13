<?php

namespace Neo4jEloquent;

use Illuminate\Support\Collection;

class RelationshipBuilder
{
    protected NodeBuilder $nodeBuilder;

    protected string $direction;

    protected string $relationshipType;

    protected array $relationshipWheres = [];

    protected array $targetLabels = [];

    public function __construct(NodeBuilder $nodeBuilder, string $direction, string $relationshipType)
    {
        $this->nodeBuilder = $nodeBuilder;
        $this->direction = $direction;
        $this->relationshipType = $relationshipType;
    }

    /**
     * Specify the target node labels.
     */
    public function label(string ...$labels): self
    {
        $this->targetLabels = $labels;

        return $this;
    }

    /**
     * Add a where condition on the relationship.
     */
    public function whereRelationship(string $field, string $operator = '=', mixed $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->relationshipWheres[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Add a where condition on the target node.
     */
    public function where(string $field, string $operator = '=', mixed $value = null): NodeBuilder
    {
        // This creates a new NodeBuilder for the target nodes
        $targetBuilder = new NodeBuilder($this->targetLabels);

        return $targetBuilder->where($field, $operator, $value);
    }

    /**
     * Execute the relationship traversal and get target nodes.
     */
    public function get(): Collection
    {
        $cypher = $this->buildTraversalCypher();
        $parameters = $this->buildParameters();

        $service = Node::getNeo4jService();
        $result = $service->run($cypher, $parameters);

        $nodes = new Collection;

        foreach ($result as $record) {
            $nodeData = $record->get('target');
            $node = $this->hydrateNode($nodeData);
            $nodes->push($node);
        }

        return $nodes;
    }

    /**
     * Get the first target node.
     */
    public function first(): ?Node
    {
        $cypher = $this->buildTraversalCypher().' LIMIT 1';
        $parameters = $this->buildParameters();

        $service = Node::getNeo4jService();
        $result = $service->run($cypher, $parameters);

        if ($result->count() === 0) {
            return null;
        }

        $nodeData = $result->first()->get('target');

        return $this->hydrateNode($nodeData);
    }

    /**
     * Count the number of target nodes.
     */
    public function count(): int
    {
        $cypher = $this->buildTraversalCypher(true);
        $parameters = $this->buildParameters();

        $service = Node::getNeo4jService();
        $result = $service->run($cypher, $parameters);

        return $result->first()->get('total');
    }

    /**
     * Build the Cypher query for relationship traversal.
     */
    protected function buildTraversalCypher(bool $countOnly = false): string
    {
        // Start with the source node match
        $sourceLabels = empty($this->nodeBuilder->getLabels()) ? '' : ':'.implode(':', $this->nodeBuilder->getLabels());
        $cypher = "MATCH (source{$sourceLabels})";

        // Add source node where conditions
        $sourceWheres = $this->nodeBuilder->getWheres();
        if (! empty($sourceWheres)) {
            $cypher .= ' WHERE '.implode(' AND ', $sourceWheres);
        }

        // Build relationship pattern
        $relationshipPattern = $this->buildRelationshipPattern();

        // Add target node labels
        $targetLabels = empty($this->targetLabels) ? '' : ':'.implode(':', $this->targetLabels);

        // Complete the match pattern
        $cypher .= " MATCH (source){$relationshipPattern}(target{$targetLabels})";

        // Add relationship where conditions
        if (! empty($this->relationshipWheres)) {
            $relationshipWhereClauses = [];
            foreach ($this->relationshipWheres as $where) {
                $relationshipWhereClauses[] = "r.{$where['field']} {$where['operator']} \${$where['field']}";
            }
            $cypher .= ' WHERE '.implode(' AND ', $relationshipWhereClauses);
        }

        // Return clause
        if ($countOnly) {
            $cypher .= ' RETURN count(target) as total';
        } else {
            $cypher .= ' RETURN target';
        }

        return $cypher;
    }

    /**
     * Build the relationship pattern based on direction.
     */
    protected function buildRelationshipPattern(): string
    {
        switch ($this->direction) {
            case 'outgoing':
                return "-[r:{$this->relationshipType}]->";
            case 'incoming':
                return "<-[r:{$this->relationshipType}]-";
            case 'both':
                return "-[r:{$this->relationshipType}]-";
            default:
                throw new \InvalidArgumentException("Invalid relationship direction: {$this->direction}");
        }
    }

    /**
     * Build query parameters.
     */
    protected function buildParameters(): array
    {
        $parameters = $this->nodeBuilder->getParameters();

        // Add relationship where parameters
        foreach ($this->relationshipWheres as $where) {
            $parameters[$where['field']] = $where['value'];
        }

        return $parameters;
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
}
