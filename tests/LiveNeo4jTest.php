<?php

namespace Neo4jEloquent\Tests;

use Neo4jEloquent\Neo4jService;
use Neo4jEloquent\Node;
use PHPUnit\Framework\TestCase;

class LiveNeo4jTest extends TestCase
{
    private Neo4jService $service;

    private array $createdNodes = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Load .env configuration
        $config = [
            'default' => 'default',
            'connections' => [
                'default' => [
                    'driver' => 'bolt',
                    'host' => env('NEO4J_HOST', 'localhost'),
                    'port' => env('NEO4J_PORT', 7687),
                    'username' => env('NEO4J_USERNAME', 'neo4j'),
                    'password' => env('NEO4J_PASSWORD', 'password'),
                    'database' => env('NEO4J_DATABASE', 'neo4j'),
                ],
            ],
            'logging' => ['enabled' => false],
        ];

        try {
            $this->service = new Neo4jService($config);
            Node::setConnectionResolver($this->service);

            // Test connection
            $this->service->run('RETURN 1 as test');
        } catch (\Exception $e) {
            $this->markTestSkipped('Neo4j connection not available: '.$e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        // Clean up test data
        foreach ($this->createdNodes as $node) {
            try {
                $node->delete();
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }

        // Clean up any remaining test data
        try {
            $this->service->run('MATCH (n:TestUser) DETACH DELETE n');
            $this->service->run('MATCH (n:TestCompany) DETACH DELETE n');
            $this->service->run('MATCH (n:TestProduct) DETACH DELETE n');
        } catch (\Exception $e) {
            // Ignore cleanup errors
        }

        parent::tearDown();
    }

    /** @test */
    public function it_can_create_a_simple_node()
    {
        echo "\n🧪 Testing: Create a simple node\n";

        $user = Node::label('TestUser')->create([
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'age' => 30,
        ]);

        $this->createdNodes[] = $user;

        $this->assertNotNull($user->getId());
        $this->assertEquals('Alice Johnson', $user->name);
        $this->assertEquals('alice@example.com', $user->email);
        $this->assertEquals(30, $user->age);

        echo "✅ Created user: {$user->name} (ID: {$user->getId()})\n";
    }

    /** @test */
    public function it_can_create_multi_label_nodes()
    {
        echo "\n🧪 Testing: Multi-label node creation\n";

        $entrepreneur = Node::label('TestUser', 'Entrepreneur')->create([
            'name' => 'Elon Musk',
            'companies' => ['Tesla', 'SpaceX', 'Neuralink'],
            'net_worth' => 200000000000,
        ]);

        $this->createdNodes[] = $entrepreneur;

        $this->assertContains('TestUser', $entrepreneur->getLabels());
        $this->assertContains('Entrepreneur', $entrepreneur->getLabels());
        $this->assertEquals('Elon Musk', $entrepreneur->name);

        echo "✅ Created entrepreneur: {$entrepreneur->name} with labels: ".implode(', ', $entrepreneur->getLabels())."\n";
    }

    /** @test */
    public function it_can_query_nodes_with_conditions()
    {
        echo "\n🧪 Testing: Query nodes with conditions\n";

        // Create test data
        $user1 = Node::label('TestUser')->create([
            'name' => 'John Doe',
            'age' => 25,
            'city' => 'San Francisco',
        ]);

        $user2 = Node::label('TestUser')->create([
            'name' => 'Jane Smith',
            'age' => 35,
            'city' => 'San Francisco',
        ]);

        $user3 = Node::label('TestUser')->create([
            'name' => 'Bob Wilson',
            'age' => 28,
            'city' => 'New York',
        ]);

        $this->createdNodes = array_merge($this->createdNodes, [$user1, $user2, $user3]);

        // Query users in San Francisco over 30
        $sfUsers = Node::label('TestUser')
            ->where('city', 'San Francisco')
            ->where('age', '>', 30)
            ->get();

        $this->assertEquals(1, $sfUsers->count());
        $this->assertEquals('Jane Smith', $sfUsers->first()->name);

        echo "✅ Found {$sfUsers->count()} users in SF over 30: {$sfUsers->first()->name}\n";

        // Query all users in San Francisco
        $allSfUsers = Node::label('TestUser')
            ->where('city', 'San Francisco')
            ->get();

        $this->assertEquals(2, $allSfUsers->count());

        echo "✅ Found {$allSfUsers->count()} total users in SF\n";
    }

    /** @test */
    public function it_can_create_and_traverse_relationships()
    {
        echo "\n🧪 Testing: Create and traverse relationships\n";

        // Create nodes
        $user = Node::label('TestUser')->create([
            'name' => 'Alice',
            'email' => 'alice@test.com',
        ]);

        $company = Node::label('TestCompany')->create([
            'name' => 'Tech Corp',
            'industry' => 'Technology',
        ]);

        $this->createdNodes = array_merge($this->createdNodes, [$user, $company]);

        // Create relationship
        $relationship = $user->relatesTo($company, 'WORKS_FOR');
        $relationship->withProperties([
            'since' => '2020-01-01',
            'position' => 'Software Engineer',
        ]);
        $relationship->save();

        $this->assertNotNull($relationship);

        echo "✅ Created WORKS_FOR relationship: {$user->name} → {$company->name}\n";
        echo "   Position: Software Engineer, Since: 2020-01-01\n";

        // Verify relationship exists in database
        $result = $this->service->run(
            'MATCH (u:TestUser {name: $userName})-[r:WORKS_FOR]->(c:TestCompany {name: $companyName}) RETURN r',
            ['userName' => 'Alice', 'companyName' => 'Tech Corp']
        );

        $this->assertTrue($result->count() > 0);

        echo "✅ Verified relationship exists in Neo4j database\n";
    }

    /** @test */
    public function it_can_update_nodes()
    {
        echo "\n🧪 Testing: Update node properties\n";

        $user = Node::label('TestUser')->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'status' => 'inactive',
        ]);

        $this->createdNodes[] = $user;

        $originalId = $user->getId();

        echo "✅ Created user: {$user->name} (Status: {$user->status})\n";

        // Update the user
        $user->update([
            'name' => 'Updated Name',
            'status' => 'active',
            'last_updated' => date('Y-m-d H:i:s'),
        ]);

        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('active', $user->status);
        $this->assertEquals($originalId, $user->getId()); // ID should remain the same

        echo "✅ Updated user: {$user->name} (Status: {$user->status})\n";

        // Verify update persisted in database
        $foundUser = Node::label('TestUser')->where('email', 'original@example.com')->first();
        $this->assertEquals('Updated Name', $foundUser->name);
        $this->assertEquals('active', $foundUser->status);

        echo "✅ Verified update persisted in database\n";
    }

    /** @test */
    public function it_can_delete_nodes()
    {
        echo "\n🧪 Testing: Delete nodes\n";

        $user = Node::label('TestUser')->create([
            'name' => 'To Be Deleted',
            'email' => 'delete@example.com',
        ]);

        $userId = $user->getId();

        echo "✅ Created user: {$user->name} (ID: {$userId})\n";

        // Delete the user
        $user->delete();

        echo "✅ Deleted user\n";

        // Verify deletion
        $foundUser = Node::label('TestUser')->where('email', 'delete@example.com')->first();
        $this->assertNull($foundUser);

        echo "✅ Verified user no longer exists in database\n";
    }

    /** @test */
    public function it_can_handle_complex_data_types()
    {
        echo "\n🧪 Testing: Complex data types and arrays\n";

        $product = Node::label('TestProduct')->create([
            'name' => 'Smartphone',
            'price' => 899.99,
            'features' => ['5G', 'Wireless Charging', 'Face ID'],
            'metadata' => [
                'brand' => 'TechBrand',
                'model' => 'Pro Max',
                'year' => 2024,
            ],
            'in_stock' => true,
            'tags' => ['electronics', 'mobile', 'premium'],
        ]);

        $this->createdNodes[] = $product;

        $this->assertEquals('Smartphone', $product->name);
        $this->assertEquals(899.99, $product->price);
        $this->assertTrue($product->in_stock);
        $this->assertIsArray($product->features);
        $this->assertContains('5G', $product->features);

        echo "✅ Created product: {$product->name} (\${$product->price})\n";
        echo '   Features: '.implode(', ', $product->features)."\n";
        echo '   In Stock: '.($product->in_stock ? 'Yes' : 'No')."\n";
    }

    /** @test */
    public function it_can_execute_raw_cypher_queries()
    {
        echo "\n🧪 Testing: Raw Cypher query execution\n";

        // Create some test data
        $user1 = Node::label('TestUser')->create(['name' => 'User 1', 'score' => 85]);
        $user2 = Node::label('TestUser')->create(['name' => 'User 2', 'score' => 92]);
        $user3 = Node::label('TestUser')->create(['name' => 'User 3', 'score' => 78]);

        $this->createdNodes = array_merge($this->createdNodes, [$user1, $user2, $user3]);

        // Execute raw Cypher to find top scoring users
        $result = $this->service->run(
            'MATCH (u:TestUser) WHERE u.score > $minScore RETURN u.name as name, u.score as score ORDER BY u.score DESC',
            ['minScore' => 80]
        );

        $this->assertTrue($result->count() >= 2);

        $topUsers = [];
        foreach ($result as $record) {
            $topUsers[] = $record->get('name').' ('.$record->get('score').')';
        }

        echo '✅ Found '.$result->count()." users with score > 80:\n";
        foreach ($topUsers as $user) {
            echo "   - {$user}\n";
        }
    }

    /** @test */
    public function it_demonstrates_full_workflow()
    {
        echo "\n🧪 Testing: Complete workflow demonstration\n";

        // 1. Create a company
        $company = Node::label('TestCompany')->create([
            'name' => 'Innovation Labs',
            'industry' => 'AI/ML',
            'founded' => 2020,
            'employees' => 50,
        ]);

        // 2. Create employees
        $ceo = Node::label('TestUser', 'Executive')->create([
            'name' => 'Sarah Connor',
            'position' => 'CEO',
            'email' => 'sarah@innovationlabs.com',
        ]);

        $developer = Node::label('TestUser', 'Developer')->create([
            'name' => 'John Code',
            'position' => 'Senior Developer',
            'email' => 'john@innovationlabs.com',
            'skills' => ['PHP', 'Laravel', 'Neo4j'],
        ]);

        $this->createdNodes = array_merge($this->createdNodes, [$company, $ceo, $developer]);

        // 3. Create relationships
        $ceoRel = $ceo->relatesTo($company, 'LEADS');
        $ceoRel->withProperties(['since' => '2020-01-01'])->save();

        $devRel = $developer->relatesTo($company, 'WORKS_FOR');
        $devRel->withProperties(['since' => '2021-06-15', 'department' => 'Engineering'])->save();

        // 4. Create colleague relationship
        $colleagueRel = $developer->relatesTo($ceo, 'REPORTS_TO');
        $colleagueRel->save();

        echo "✅ Created company: {$company->name}\n";
        echo "✅ Created CEO: {$ceo->name} ({$ceo->position})\n";
        echo "✅ Created Developer: {$developer->name} ({$developer->position})\n";
        echo "✅ Created LEADS relationship: {$ceo->name} → {$company->name}\n";
        echo "✅ Created WORKS_FOR relationship: {$developer->name} → {$company->name}\n";
        echo "✅ Created REPORTS_TO relationship: {$developer->name} → {$ceo->name}\n";

        // 5. Query the network
        $companyEmployees = $this->service->run(
            'MATCH (u:TestUser)-[:WORKS_FOR|LEADS]->(c:TestCompany {name: $companyName}) RETURN u.name as name, u.position as position',
            ['companyName' => 'Innovation Labs']
        );

        echo "\n📊 Company Network Analysis:\n";
        echo "   Company: {$company->name} ({$company->employees} employees)\n";
        echo "   Team Members:\n";

        foreach ($companyEmployees as $employee) {
            echo "   - {$employee->get('name')} ({$employee->get('position')})\n";
        }

        $this->assertEquals(2, $companyEmployees->count());

        echo "\n✅ Full workflow completed successfully!\n";
    }
}
