<?php

namespace Neo4jEloquent\Console\Commands;

use Illuminate\Console\Command;
use Neo4jEloquent\Neo4jService;

class DebugNeo4j extends Command
{
    protected $signature = 'neo4j:debug';

    protected $description = 'Debug Neo4j connection';

    public function handle()
    {
        $config = config('neo4j');

        $this->info('Neo4j Configuration:');
        $this->line('Username: '.var_export($config['connections']['default']['username'], true));
        $this->line('Password: '.var_export($config['connections']['default']['password'], true));
        
        // Handle both old and new config formats
        if (isset($config['connections']['default']['host'])) {
            $this->line('Host: '.$config['connections']['default']['host']);
            $this->line('Port: '.$config['connections']['default']['port']);
        } else {
            $this->line('URI: '.$config['connections']['default']['uri']);
        }
        
        $this->line('Database: '.$config['connections']['default']['database']);
        $this->line('Empty username: '.(empty($config['connections']['default']['username']) ? 'true' : 'false'));
        $this->line('Empty password: '.(empty($config['connections']['default']['password']) ? 'true' : 'false'));

        try {
            $service = app(Neo4jService::class);
            $result = $service->run('RETURN 1 as test');
            $this->info('Connection successful!');
            $this->line('Result: '.$result->first()->get('test'));
        } catch (\Exception $e) {
            $this->error('Connection failed: '.$e->getMessage());
            $this->error('Exception type: '.get_class($e));
        }
    }
}
