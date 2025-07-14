<?php

namespace Neo4jEloquent\Console\Commands;

use Illuminate\Console\Command;
use Neo4jEloquent\Neo4jService;

class ClearNeo4j extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'neo4j:clear 
                            {--fake-only : Only clear fake data (preserves real data)}
                            {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear data from Neo4j database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fakeOnly = $this->option('fake-only');
        $force = $this->option('force');

        $this->warn('⚠️  Neo4j Database Clear Operation');
        $this->info('');

        if ($fakeOnly) {
            $this->info('Mode: Clear FAKE data only (preserves real data)');
            $this->info('Will remove labels: FakeUser, FakeCompany, FakeProduct, FakeCategory, FakeLocation, FakeExecutive, FakeEmployee');
        } else {
            $this->error('Mode: Clear ALL data (DESTRUCTIVE)');
            $this->error('This will remove ALL nodes and relationships from the database!');
        }

        $this->info('');

        if (! $force) {
            $confirmMessage = $fakeOnly
                ? 'Are you sure you want to clear all fake data?'
                : 'Are you ABSOLUTELY sure you want to clear ALL data?';

            if (! $this->confirm($confirmMessage, false)) {
                $this->info('Operation cancelled.');

                return 0;
            }

            if (! $fakeOnly) {
                $this->error('FINAL WARNING: This will delete EVERYTHING!');
                if (! $this->confirm('Type "yes" to confirm total data destruction', false)) {
                    $this->info('Operation cancelled.');

                    return 0;
                }
            }
        }

        try {
            // Use the configured Neo4j service from the container
            $service = app(Neo4jService::class);

            $this->info('🧹 Starting database clear operation...');

            if ($fakeOnly) {
                // Clear only fake data
                $fakeLabels = ['FakeUser', 'FakeCompany', 'FakeProduct', 'FakeCategory', 'FakeLocation', 'FakeExecutive', 'FakeEmployee'];

                foreach ($fakeLabels as $label) {
                    $this->info("   Clearing {$label} nodes...");
                    $result = $service->run("MATCH (n:{$label}) DETACH DELETE n");
                }

                $this->info('✅ Fake data cleared successfully!');
            } else {
                // Clear all data
                $this->info('   Clearing ALL nodes and relationships...');
                $service->run('MATCH (n) DETACH DELETE n');

                $this->info('✅ All data cleared successfully!');
                $this->warn('   Database is now empty.');
            }

            // Show remaining node count
            $result = $service->run('MATCH (n) RETURN count(n) as count');
            $remainingNodes = $result->first()->get('count');

            $this->info('');
            $this->info("📊 Remaining nodes in database: {$remainingNodes}");

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Clear operation failed:');
            $this->error($e->getMessage());

            if ($this->option('verbose')) {
                $this->error('Stack trace:');
                $this->error($e->getTraceAsString());
            }

            return 1;
        }
    }
}
