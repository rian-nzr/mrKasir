<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WelcomeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'welcome';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show welcome message and available commands for MrKasir POS';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🏪 Welcome to MrKasir POS System!');
        $this->newLine();
        
        $this->info('📋 Available Database Commands:');
        $this->newLine();
        
        $commands = [
            ['Command', 'Description', 'Usage'],
            ['db:fresh-seed', 'Reset database with seeders', 'php artisan db:fresh-seed --force'],
            ['db:status', 'Show database status & health', 'php artisan db:status'],
            ['db:backup', 'Create database backup', 'php artisan db:backup'],
            ['user:permissions', 'Check user permissions', 'php artisan user:permissions email@example.com'],
            ['test:superadmin-access', 'Test super admin access', 'php artisan test:superadmin-access'],
            ['welcome', 'Show this help message', 'php artisan welcome'],
        ];
        
        $this->table($commands[0], array_slice($commands, 1));
        
        $this->newLine();
        $this->info('🔑 Default Login Credentials:');
        $this->table(
            ['Role', 'Email', 'Password', 'Access'],
            [
                ['Super Admin', 'superadmin@example.com', 'password', 'All Stores'],
                ['Kasir A', 'kasir.tokoa@example.com', 'password', 'Toko A'],
                ['Kasir B', 'kasir.tokob@example.com', 'password', 'Toko B'],
                ['Admin A', 'admin.tokoa@example.com', 'password', 'Toko A'],
            ]
        );
        
        $this->newLine();
        $this->info('🚀 Quick Start:');
        $this->line('1. Run: <info>php artisan db:fresh-seed --force</info> (reset & seed database)');
        $this->line('2. Run: <info>php artisan serve</info> (start development server)');
        $this->line('3. Login with Super Admin credentials');
        $this->line('4. Check: <info>php artisan db:status</info> (verify data)');
        
        $this->newLine();
        $this->info('📚 Documentation:');
        $this->line('• Database Seeders: <comment>DATABASE_SEEDERS_DOCUMENTATION.md</comment>');
        $this->line('• Super Admin Fix: <comment>SUPER_ADMIN_ACCESS_FIX.md</comment>');
        $this->line('• Backup Location: <comment>storage/app/backups/</comment>');
        
        $this->newLine();
        $this->info('✨ Features Ready:');
        $this->line('✅ Multi-store architecture');
        $this->line('✅ Role-based access control');
        $this->line('✅ Complete product management');
        $this->line('✅ Payment method integration');
        $this->line('✅ Cashier shift system');
        $this->line('✅ User management');
        $this->line('✅ Database backup & monitoring');
        $this->line('🔥 Super Admin FULL ACCESS (All Stores)');
        
        $this->newLine();
        $this->info('🔧 Need Help?');
        $this->line('• Run any command with <info>--help</info> for details');
        $this->line('• Check logs in <comment>storage/logs/</comment>');
        $this->line('• Database issues? Run <info>php artisan db:fresh-seed --force</info>');
        $this->line('• Super Admin issues? Run <info>php artisan test:superadmin-access</info>');
        
        return Command::SUCCESS;
    }
}
