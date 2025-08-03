<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class FreshDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:fresh-seed 
                            {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop all tables, run migrations and seed the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('force') || $this->confirm('This will drop all tables and reset the database. Do you want to continue?')) {
            $this->info('Dropping all tables and running fresh migrations...');
            
            // Run migrate:fresh
            Artisan::call('migrate:fresh', [], $this->getOutput());
            
            $this->info('Running database seeders...');
            
            // Run seeders
            Artisan::call('db:seed', [], $this->getOutput());
            
            $this->info('✅ Database has been reset and seeded successfully!');
            
            $this->newLine();
            $this->info('Default users created:');
            $this->table(
                ['Role', 'Email', 'Password', 'Store'],
                [
                    ['Super Admin', 'superadmin@example.com', 'password', 'All Stores'],
                    ['Kasir', 'kasir.tokoa@example.com', 'password', 'Toko A'],
                    ['Kasir', 'kasir.tokob@example.com', 'password', 'Toko B'],
                ]
            );
            
            $this->newLine();
            $this->info('Stores created:');
            $this->table(
                ['Name', 'Code', 'Address'],
                [
                    ['Toko A', 'TOKO-A', 'Jl. Contoh No. 1, Jakarta'],
                    ['Toko B', 'TOKO-B', 'Jl. Contoh No. 2, Bandung'],
                ]
            );
            
            return Command::SUCCESS;
        }
        
        $this->info('Operation cancelled.');
        return Command::FAILURE;
    }
}
