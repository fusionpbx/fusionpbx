<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo domain
        $domainUuid = Str::uuid()->toString();
        \DB::table('v_domains')->insert([
            'domain_uuid' => $domainUuid,
            'domain_name' => 'demo.fusionpbx.com',
            'domain_enabled' => 'true',
            'domain_description' => 'Demo Domain',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create admin user
        $adminUuid = Str::uuid()->toString();
        \DB::table('v_users')->insert([
            'user_uuid' => $adminUuid,
            'domain_uuid' => $domainUuid,
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'user_enabled' => 'true',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create demo extensions
        for ($i = 1000; $i <= 1004; $i++) {
            \DB::table('v_extensions')->insert([
                'extension_uuid' => Str::uuid()->toString(),
                'domain_uuid' => $domainUuid,
                'extension' => (string)$i,
                'password' => Str::random(16),
                'enabled' => 'true',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Demo data seeded successfully!');
        $this->command->info('Domain: demo.fusionpbx.com');
        $this->command->info('Admin: admin / admin123');
        $this->command->info('Extensions: 1000-1004');
    }
}
