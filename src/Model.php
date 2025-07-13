<?php

namespace Neo4jEloquent;

/**
 * Base model class for Neo4j Eloquent models.
 * Provides a convenient way to define models with predefined labels.
 */
abstract class Model extends Node
{
    /**
     * The labels for the node.
     * Child classes should override this property.
     */
    protected array $labels = [];

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast.
     */
    protected array $casts = [];

    /**
     * Create a new model instance.
     */
    public function __construct(array $attributes = [], array $labels = [])
    {
        // Use the model's labels if no labels provided
        if (empty($labels) && !empty($this->labels)) {
            $labels = $this->labels;
        }

        parent::__construct($attributes, $labels);

        // Apply casts to attributes
        $this->applyCasts();
    }

    /**
     * Fill the model with an array of attributes.
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    /**
     * Determine if the given attribute may be mass assigned.
     */
    public function isFillable(string $key): bool
    {
        // If fillable is empty, allow all attributes
        if (empty($this->fillable)) {
            return true;
        }

        return in_array($key, $this->fillable);
    }

    /**
     * Create a new instance of the model.
     */
    public static function create(array $attributes = []): static
    {
        $instance = new static();
        $instance->fill($attributes);
        $instance->save();

        return $instance;
    }

    /**
     * Apply casts to the attributes.
     */
    protected function applyCasts(): void
    {
        foreach ($this->casts as $key => $cast) {
            if (isset($this->attributes[$key])) {
                $this->attributes[$key] = $this->castAttribute($key, $this->attributes[$key], $cast);
            }
        }
    }

    /**
     * Cast an attribute to a specific type.
     */
    protected function castAttribute(string $key, mixed $value, string $cast): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($cast) {
            'integer', 'int' => (int) $value,
            'float', 'double' => (float) $value,
            'string' => (string) $value,
            'boolean', 'bool' => (bool) $value,
            'array' => is_array($value) ? $value : json_decode($value, true),
            'object' => is_object($value) ? $value : json_decode($value),
            'datetime' => $this->castToDateTime($value),
            default => $value,
        };
    }

    /**
     * Cast value to datetime.
     */
    protected function castToDateTime(mixed $value): ?\DateTime
    {
        if ($value instanceof \DateTime) {
            return $value;
        }

        if (is_string($value)) {
            return new \DateTime($value);
        }

        if (is_numeric($value)) {
            return new \DateTime('@' . $value);
        }

        return null;
    }

    /**
     * Override setAttribute to apply casts when setting attributes.
     */
    public function setAttribute(string $key, mixed $value): self
    {
        // Apply cast if defined
        if (isset($this->casts[$key])) {
            $value = $this->castAttribute($key, $value, $this->casts[$key]);
        }

        return parent::setAttribute($key, $value);
    }
}
