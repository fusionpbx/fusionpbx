<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupCommand extends Command
{
    protected $signature = 'fusionpbx:setup';
    protected $description = 'Setup FusionPBX Laravel application';

    public function handle(): int
    {
        $this->info('🚀 Setting up FusionPBX...');
        
        // Run migrations
        $this->call('migrate');
        
        // Seed database
        if ($this->confirm('Would you like to seed demo data?', true)) {
            $this->call('db:seed', ['--class' => 'DemoDataSeeder']);
        }
        
        $this->info('✅ Setup complete!');
        $this->info('📧 Default admin: admin@demo.fusionpbx.com');
        $this->info('🔑 Default password: admin123');
        
        return self::SUCCESS;
    }
}
