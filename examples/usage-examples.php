<?php

/**
 * Neo4j Eloquent Usage Examples
 * 
 * This file demonstrates the schema-less, dynamic API of the Neo4j Eloquent package.
 * Run individual examples using php artisan tinker.
 */

use Neo4jEloquent\Node;

echo "=== Neo4j Eloquent API Examples ===" . PHP_EOL . PHP_EOL;

/**
 * EXAMPLE 1: Basic Node Creation (Schema-less)
 * Create any type of node without predefined models
 */
echo "--- Example 1: Schema-less Node Creation ---" . PHP_EOL;

// Create a Person node dynamically
$person = Node::label('Person')->create([
    'name' => 'Alice Johnson',
    'email' => 'alice@example.com',
    'age' => 30,
    'city' => 'San Francisco'
]);

echo "Created Person: {$person->name} (ID: {$person->getId()})" . PHP_EOL;

// Create a Company node with multiple labels
$company = Node::label('Company', 'Organization')->create([
    'name' => 'Tech Innovations Inc',
    'industry' => 'Technology',
    'founded' => 2015,
    'headquarters' => 'Silicon Valley'
]);

echo "Created Company: {$company->name}" . PHP_EOL . PHP_EOL;

/**
 * EXAMPLE 2: Flexible Querying
 * Query any node type without predefined models
 */
echo "--- Example 2: Dynamic Querying ---" . PHP_EOL;

// Find all people in San Francisco
$sfPeople = Node::label('Person')
    ->where('city', 'San Francisco')
    ->where('age', '>', 25)
    ->orderBy('name')
    ->get();

echo "Found {$sfPeople->count()} people in SF over 25" . PHP_EOL;

// Find companies in tech industry
$techCompanies = Node::label('Company')
    ->where('industry', 'Technology')
    ->where('founded', '>', 2010)
    ->limit(5)
    ->get();

echo "Found {$techCompanies->count()} tech companies founded after 2010" . PHP_EOL . PHP_EOL;

/**
 * EXAMPLE 3: Multi-Label Support
 * Handle nodes with multiple labels seamlessly
 */
echo "--- Example 3: Multi-Label Nodes ---" . PHP_EOL;

// Create a person who is also an entrepreneur
$entrepreneur = Node::label('Person', 'Entrepreneur')->create([
    'name' => 'Elon Musk',
    'email' => 'elon@spacex.com',
    'companies_founded' => ['PayPal', 'Tesla', 'SpaceX'],
    'net_worth' => 200000000000
]);

// Query for all entrepreneurs
$entrepreneurs = Node::label('Entrepreneur')->get();
echo "Found {$entrepreneurs->count()} entrepreneurs" . PHP_EOL . PHP_EOL;

/**
 * EXAMPLE 4: Relationship Traversal
 * Navigate graph relationships fluently
 */
echo "--- Example 4: Relationship Traversal ---" . PHP_EOL;

// Note: These examples show the API - actual execution requires Neo4j connection

// Find all friends of Alice
echo "API Example - Find Alice's friends:" . PHP_EOL;
echo "Node::label('Person')" . PHP_EOL;
echo "    ->where('name', 'Alice Johnson')" . PHP_EOL;
echo "    ->outgoing('FRIENDS_WITH')" . PHP_EOL;
echo "    ->label('Person')" . PHP_EOL;
echo "    ->get();" . PHP_EOL . PHP_EOL;

// Find companies that Alice works for
echo "API Example - Find Alice's employers:" . PHP_EOL;
echo "Node::label('Person')" . PHP_EOL;
echo "    ->where('name', 'Alice Johnson')" . PHP_EOL;
echo "    ->outgoing('WORKS_FOR')" . PHP_EOL;
echo "    ->label('Company')" . PHP_EOL;
echo "    ->get();" . PHP_EOL . PHP_EOL;

/**
 * EXAMPLE 5: Arbitrary Node Types
 * Create any type of node on the fly
 */
echo "--- Example 5: Arbitrary Node Types ---" . PHP_EOL;

// Create a product node
$product = Node::label('Product')->create([
    'name' => 'iPhone 15',
    'price' => 999,
    'category' => 'Electronics',
    'brand' => 'Apple',
    'features' => ['5G', 'Face ID', 'Wireless Charging']
]);

// Create a review node
$review = Node::label('Review')->create([
    'rating' => 5,
    'title' => 'Excellent phone!',
    'content' => 'Love the new features and camera quality.',
    'verified_purchase' => true
]);

// Create a location node
$location = Node::label('Location', 'Store')->create([
    'name' => 'Apple Store Union Square',
    'address' => '300 Post St, San Francisco, CA',
    'coordinates' => ['lat' => 37.7879, 'lng' => -122.4075],
    'store_type' => 'flagship'
]);

echo "Created Product: {$product->name}" . PHP_EOL;
echo "Created Review: {$review->title}" . PHP_EOL;
echo "Created Location: {$location->name}" . PHP_EOL . PHP_EOL;

/**
 * EXAMPLE 6: Complex Queries
 * Build sophisticated queries dynamically
 */
echo "--- Example 6: Complex Queries ---" . PHP_EOL;

// Query for high-value customers
echo "API Example - High-value customers:" . PHP_EOL;
echo "Node::label('Customer')" . PHP_EOL;
echo "    ->where('total_spent', '>', 1000)" . PHP_EOL;
echo "    ->where('account_status', 'premium')" . PHP_EOL;
echo "    ->whereIn('country', ['US', 'CA', 'UK'])" . PHP_EOL;
echo "    ->orderBy('total_spent', 'desc')" . PHP_EOL;
echo "    ->limit(20)" . PHP_EOL;
echo "    ->get();" . PHP_EOL . PHP_EOL;

/**
 * EXAMPLE 7: Node Updates and Deletion
 * Modify nodes dynamically
 */
echo "--- Example 7: Updates and Deletion ---" . PHP_EOL;

// Update a node
$person = Node::label('Person')->where('name', 'Alice Johnson')->first();
if ($person) {
    $person->age = 31;
    $person->last_updated = now();
    // $person->save(); // Would update in database
    echo "Updated Alice's age to {$person->age}" . PHP_EOL;
}

// Bulk update
echo "API Example - Bulk update:" . PHP_EOL;
echo "Node::label('Product')" . PHP_EOL;
echo "    ->where('category', 'Electronics')" . PHP_EOL;
echo "    ->update(['discounted' => true, 'discount_percent' => 10]);" . PHP_EOL . PHP_EOL;

echo "=== Examples Complete ===" . PHP_EOL;
echo "Key Benefits:" . PHP_EOL;
echo "✅ No predefined models required" . PHP_EOL;
echo "✅ Dynamic node types and properties" . PHP_EOL;
echo "✅ Eloquent-style fluent API" . PHP_EOL;
echo "✅ Multi-label support" . PHP_EOL;
echo "✅ Relationship traversal" . PHP_EOL;
echo "✅ Familiar Laravel patterns" . PHP_EOL;
