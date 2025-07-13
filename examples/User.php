<?php

namespace App\Models\Neo4j;

use Neo4jEloquent\Model;

class User extends Model
{
    /**
     * The labels for the node.
     */
    protected array $labels = ['User'];

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [
        'name',
        'email',
        'age',
        'city',
    ];

    /**
     * The attributes that should be cast.
     */
    protected array $casts = [
        'age' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
