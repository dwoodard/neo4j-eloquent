# Neo4j Eloquent

[![Latest Version](https://img.shields.io/github/v/release/dwoodard/neo4j-eloquent)](https://github.com/dwoodard/neo4j-eloquent/releases)
[![License](https://img.shields.io/github/license/dwoodard/neo4j-eloquent)](LICENSE)

A Laravel package that provides an Eloquent-style API for Neo4j graph database interactions with schema-less, dynamic operations.

## Features

✅ **Schema-less Operations** - No predefined models required  
✅ **Dynamic Node Types** - Create any node type on the fly  
✅ **Eloquent-style API** - Familiar Laravel query patterns  
✅ **Multi-label Support** - Handle nodes with multiple labels  
✅ **Relationship Traversal** - Fluent relationship navigation  
✅ **Laravel Integration** - Seamless service provider integration  

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
```

## Basic Usage

### Creating Nodes (Schema-less)

```php
use Neo4jEloquent\Node;

// Create any type of node without predefined models
$person = Node::label('Person')->create([
    'name' => 'Alice Johnson',
    'email' => 'alice@example.com',
    'age' => 30,
    'city' => 'San Francisco'
]);

// Multi-label nodes
$company = Node::label('Company', 'Organization')->create([
    'name' => 'Tech Innovations Inc',
    'industry' => 'Technology',
    'founded' => 2015
]);
```

### Querying Nodes

```php
// Find all people in San Francisco
$sfPeople = Node::label('Person')
    ->where('city', 'San Francisco')
    ->where('age', '>', 25)
    ->orderBy('name')
    ->get();

// Find a specific person
$alice = Node::label('Person')
    ->where('name', 'Alice Johnson')
    ->first();

// Count nodes
$techCompanyCount = Node::label('Company')
    ->where('industry', 'Technology')
    ->count();
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

### Updating and Deleting

```php
// Update a single node
$person = Node::label('Person')->find('uuid-123');
$person->age = 31;
$person->save();

// Bulk updates
Node::label('Product')
    ->where('category', 'Electronics')
    ->update(['discounted' => true, 'discount_percent' => 10]);

// Delete nodes
Node::label('TempData')
    ->where('expires_at', '<', now())
    ->delete();
```

### Working with Arbitrary Node Types

The beauty of Neo4j Eloquent is that you can work with any node type without creating predefined model classes:

```php
// Create a product
$product = Node::label('Product')->create([
    'name' => 'iPhone 15',
    'price' => 999,
    'category' => 'Electronics',
    'features' => ['5G', 'Face ID', 'Wireless Charging']
]);

// Create a review
$review = Node::label('Review')->create([
    'rating' => 5,
    'title' => 'Excellent phone!',
    'content' => 'Love the new features.',
    'verified_purchase' => true
]);

// Create a location
$store = Node::label('Location', 'Store')->create([
    'name' => 'Apple Store Union Square',
    'address' => '300 Post St, San Francisco, CA',
    'coordinates' => ['lat' => 37.7879, 'lng' => -122.4075]
]);
```

## Advanced Features

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

## Testing

Run the package tests:

```bash
php artisan test tests/Feature/Neo4jEloquentTest.php
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

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

- [Documentation](https://github.com/dwoodard/neo4j-eloquent/wiki)
- [Issues](https://github.com/dwoodard/neo4j-eloquent/issues)
- [Discussions](https://github.com/dwoodard/neo4j-eloquent/discussions)
