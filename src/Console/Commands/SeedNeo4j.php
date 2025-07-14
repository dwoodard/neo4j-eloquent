<?php

namespace Neo4jEloquent\Console\Commands;

use Illuminate\Console\Command;

class SeedNeo4j extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'neo4j:seed 
                            {--users=50 : Number of users to create}
                            {--companies=20 : Number of companies to create}
                            {--products=100 : Number of products to create}
                            {--categories=15 : Number of categories to create}
                            {--locations=30 : Number of locations to create}
                            {--clear : Clear existing fake data before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the Neo4j database with fake data using Faker';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Neo4j database seeding...');
        $this->info('');

        if ($this->option('clear')) {
            $this->warn('⚠️  This will clear ALL existing fake data in Neo4j');
            if (! $this->confirm('Are you sure you want to continue?', false)) {
                $this->info('Seeding cancelled.');

                return 0;
            }
        }

        $this->info('Configuration:');
        $this->info("  - Users: {$this->option('users')}");
        $this->info("  - Companies: {$this->option('companies')}");
        $this->info("  - Products: {$this->option('products')}");
        $this->info("  - Categories: {$this->option('categories')}");
        $this->info("  - Locations: {$this->option('locations')}");
        $this->info('');

        try {
            // Check if Neo4jSeeder exists in the application
            if (class_exists('Database\Seeders\Neo4jSeeder')) {
                $seeder = new \Database\Seeders\Neo4jSeeder;
                $seeder->setCommand($this);
                $seeder->run();
            } else {
                $this->error('Neo4jSeeder class not found in Database\Seeders namespace.');
                $this->info('Please create a Neo4jSeeder class in your application\'s database/seeders directory.');

                return 1;
            }

            $this->info('');
            $this->info('🎉 Neo4j seeding completed successfully!');
            $this->info('');
            $this->info('You can now:');
            $this->info('  • View the data in Neo4j Browser: http://localhost:7474');
            $this->info('  • Run queries like: MATCH (n:FakeUser) RETURN n LIMIT 10');
            $this->info('  • Explore relationships: MATCH (u:FakeUser)-[r]->(c:FakeCompany) RETURN u,r,c LIMIT 10');

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Neo4j seeding failed:');
            $this->error($e->getMessage());

            if ($this->option('verbose')) {
                $this->error('Stack trace:');
                $this->error($e->getTraceAsString());
            }

            return 1;
        }
    }
}
