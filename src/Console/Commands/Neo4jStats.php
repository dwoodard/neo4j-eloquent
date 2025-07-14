<?php

namespace Neo4jEloquent\Console\Commands;

use Illuminate\Console\Command;
use Neo4jEloquent\Neo4jService;

class Neo4jStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'neo4j:stats {--detailed : Show detailed breakdown by label}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show Neo4j database statistics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // Use the configured Neo4j service from the container
            $service = app(Neo4jService::class);
            
            $this->info('📊 Neo4j Database Statistics');
            $this->info('============================');
            $this->info('');
            
            // Total nodes
            $result = $service->run('MATCH (n) RETURN count(n) as count');
            $totalNodes = $result->first()->get('count');
            
            // Total relationships
            $result = $service->run('MATCH ()-[r]-() RETURN count(r) as count');
            $totalRelationships = $result->first()->get('count');
            
            // Total labels
            $result = $service->run('CALL db.labels()');
            $labels = [];
            foreach ($result as $record) {
                $labels[] = $record->get('label');
            }
            
            // Total relationship types
            $result = $service->run('CALL db.relationshipTypes()');
            $relationshipTypes = [];
            foreach ($result as $record) {
                $relationshipTypes[] = $record->get('relationshipType');
            }
            
            $this->info("🔢 Total Nodes: {$totalNodes}");
            $this->info("🔗 Total Relationships: {$totalRelationships}");
            $this->info("🏷️  Total Labels: " . count($labels));
            $this->info("⚡ Total Relationship Types: " . count($relationshipTypes));
            $this->info('');
            
            if ($this->option('detailed')) {
                // Node counts by label
                $this->info('📋 Node Counts by Label:');
                $this->info('------------------------');
                
                foreach ($labels as $label) {
                    $result = $service->run("MATCH (n:`{$label}`) RETURN count(n) as count");
                    $count = $result->first()->get('count');
                    $this->info("   {$label}: {$count}");
                }
                
                $this->info('');
                
                // Relationship counts by type
                $this->info('🔗 Relationship Counts by Type:');
                $this->info('-------------------------------');
                
                foreach ($relationshipTypes as $type) {
                    $result = $service->run("MATCH ()-[r:`{$type}`]-() RETURN count(r) as count");
                    $count = $result->first()->get('count');
                    $this->info("   {$type}: {$count}");
                }
                
                $this->info('');
            }
            
            // Recent activity (if fake data exists)
            $result = $service->run('MATCH (n:FakeUser) RETURN count(n) as count');
            $fakeUsers = $result->first()->get('count');
            
            if ($fakeUsers > 0) {
                $this->info('🎭 Fake Data Summary:');
                $this->info('--------------------');
                
                $fakeLabels = ['FakeUser', 'FakeCompany', 'FakeProduct', 'FakeCategory', 'FakeLocation'];
                foreach ($fakeLabels as $label) {
                    $result = $service->run("MATCH (n:`{$label}`) RETURN count(n) as count");
                    $count = $result->first()->get('count');
                    if ($count > 0) {
                        $this->info("   {$label}: {$count}");
                    }
                }
                $this->info('');
            }
            
            // Connection info
            $config = config('neo4j');
            $this->info('🌐 Connection Info:');
            $this->info('------------------');
            
            if (isset($config['connections']['default']['host'])) {
                $this->info("   Host: {$config['connections']['default']['host']}:{$config['connections']['default']['port']}");
            } else {
                $this->info("   URI: {$config['connections']['default']['uri']}");
            }
            
            $this->info("   Database: {$config['connections']['default']['database']}");
            $this->info("   Username: {$config['connections']['default']['username']}");
            $this->info('');
            
            // Helpful queries
            $this->info('💡 Helpful Queries:');
            $this->info('------------------');
            $this->info('   View all labels: CALL db.labels()');
            $this->info('   View sample users: MATCH (n:FakeUser) RETURN n LIMIT 10');
            $this->info('   View relationships: MATCH (u:FakeUser)-[r]->(c:FakeCompany) RETURN u,r,c LIMIT 10');
            $this->info('   View network: MATCH (n)-[r]-(m) RETURN n,r,m LIMIT 50');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to get statistics:');
            $this->error($e->getMessage());
            
            if ($this->option('verbose')) {
                $this->error('Stack trace:');
                $this->error($e->getTraceAsString());
            }
            
            return 1;
        }
    }
}
