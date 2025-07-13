# Neo4j Eloquent

[![Latest Version](https://img.shields.io/packagist/v/dwoodard/neo4j-eloquent.svg)](https://packagist.org/packages/dwoodard/neo4j-eloquent)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A Laravel package that provides an Eloquent-style API for Neo4j graph database interactions with schema-less, dynamic operations.

## Features

✅ **Schema-less Operations** - No predefined models required  
✅ **Eloquent-style API** - Familiar Laravel query patterns  
✅ **Multi-label Support** - Handle nodes with multiple labels  
✅ **Relationship Traversal** - Fluent relationship navigation  
✅ **Laravel Integration** - Seamless service provider integration  
✅ **Model-based Approach** - Traditional Eloquent-style models  
✅ **JSON Serialization** - Proper JSON encoding out of the box  
✅ **Auto Service Injection** - No manual configuration required  

## Installation

Install the package via Composer:

```bash
composer require dwoodard/neo4j-eloquent
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Neo4jEloquent\Laravel\Neo4jEloquentServiceProvider"
```

## Configuration

Add your Neo4j connection details to your `.env` file:

```env
NEO4J_HOST=localhost
NEO4J_PORT=7687
NEO4J_USERNAME=neo4j
NEO4J_PASSWORD=your-password
NEO4J_DATABASE=neo4j
NEO4J_LOG_QUERIES=false
NEO4J_AUTO_UUID=true
```

**That's it!** The package will automatically configure itself and inject the Neo4j service.

## Usage

### Model-based Approach (Recommended)

Create a model by extending the base `Model` class:

```php
<?php

namespace App\Models\Neo4j;

use Neo4jEloquent\Model;

class User extends Model
{
    protected array $labels = ['User'];
    
    protected array $fillable = [
        'name',
        'email',
        'age',
        'city',
    ];
    
    protected array $casts = [
        'age' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
```

Now you can use familiar Eloquent methods:

```php
// Create a user
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
    'city' => 'San Francisco'
]);

// Find all users
$users = User::all();

// Find a specific user
$user = User::find($userId);

// Query with conditions
$adults = User::label('User')
    ->where('age', '>', 18)
    ->orderBy('name')
    ->get();
```

### Schema-less Operations

Create any type of node without predefined models:

```php
use Neo4jEloquent\Node;

// Create any type of node
$product = Node::label('Product')->create([
    'name' => 'iPhone 15',
    'price' => 999,
    'category' => 'Electronics',
    'features' => ['5G', 'Face ID', 'Wireless Charging']
]);

// Multi-label nodes
$company = Node::label('Company', 'Organization')->create([
    'name' => 'Tech Innovations Inc',
    'industry' => 'Technology',
    'founded' => 2015
]);

// Query any nodes
$techProducts = Node::label('Product')
    ->where('category', 'Electronics')
    ->where('price', '>', 500)
    ->orderBy('price', 'desc')
    ->get();
```

### Relationship Traversal

```php
// Find Alice's friends
$friends = Node::label('Person')
    ->where('name', 'Alice Johnson')
    ->outgoing('FRIENDS_WITH')
    ->label('Person')
    ->get();

// Find companies Alice works for
$employers = Node::label('Person')
    ->where('name', 'Alice Johnson')
    ->outgoing('WORKS_FOR')
    ->label('Company')
    ->get();

// Bidirectional relationships
$connections = Node::label('Person')
    ->where('name', 'Alice')
    ->related('KNOWS')
    ->label('Person')
    ->get();
```

### JSON Serialization

All Node and Model instances automatically serialize to JSON properly:

```php
$users = User::all();

// This will return properly formatted JSON with all node data
return response()->json($users);

// Or convert to array manually
$usersArray = $users->map(fn($user) => $user->toArray());
```

### Raw Cypher Queries

```php
use Neo4jEloquent\Neo4jService;

$neo4j = app(Neo4jService::class);

$result = $neo4j->runRaw('
    MATCH (p:Person)-[:FRIENDS_WITH]->(f:Person)
    WHERE p.city = $city
    RETURN p.name, count(f) as friend_count
', ['city' => 'San Francisco']);
```

## Testing Connection

Test your Neo4j connection:

```bash
php artisan make:command TestNeo4j
```

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Neo4jEloquent\Node;

class TestNeo4j extends Command
{
    protected $signature = 'neo4j:test';
    protected $description = 'Test Neo4j connection';

    public function handle()
    {
        try {
            $testNode = Node::label('TestNode')->create([
                'name' => 'Test',
                'created_at' => now()
            ]);
            
            $this->info('✅ Neo4j connection successful!');
            
            // Clean up
            $testNode->delete();
            
        } catch (\Exception $e) {
            $this->error('❌ Neo4j connection failed: ' . $e->getMessage());
        }
    }
}
```

## Advanced Features

### Multi-Label Operations

```php
// Query nodes with multiple labels
$managers = Node::label('Person', 'Employee', 'Manager')
    ->where('department', 'Engineering')
    ->get();

// Add labels dynamically
$person = Node::label('Person')->find('uuid-123');
$person->addLabel('VIP');
$person->addLabel('Premium');
```

### Complex Queries

```php
// Query with multiple conditions
$premiumCustomers = Node::label('Customer')
    ->where('total_spent', '>', 1000)
    ->where('account_status', 'premium')
    ->whereIn('country', ['US', 'CA', 'UK'])
    ->orderBy('total_spent', 'desc')
    ->limit(20)
    ->get();
```

## Configuration Options

The package configuration file (`config/neo4j.php`) supports:

- Multiple connection configurations
- Query logging for debugging
- Auto UUID generation
- Custom field mappings

## Requirements

- PHP 8.2+
- Laravel 10.0+
- Neo4j 4.0+ database

## What's Fixed in This Version

This package includes several important fixes:

1. **Automatic Service Injection** - No need to manually set up the Neo4j service
2. **Proper JSON Serialization** - Node objects serialize correctly in API responses
3. **Eloquent-style Methods** - `all()`, `find()`, `create()` work out of the box
4. **Model Base Class** - Easier model creation with fillable attributes and casts
5. **Type Safety** - Proper type declarations throughout

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

- [Documentation](https://github.com/dwoodard/neo4j-eloquent/wiki)
- [Issues](https://github.com/dwoodard/neo4j-eloquent/issues)
- [Discussions](https://github.com/dwoodard/neo4j-eloquent/discussions)
