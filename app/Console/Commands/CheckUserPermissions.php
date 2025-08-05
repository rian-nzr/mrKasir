<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class CheckUserPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:permissions {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check permissions for a specific user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return Command::FAILURE;
        }
        
        $this->info("🔍 Permission Check for: {$user->name} ({$user->email})");
        $this->newLine();
        
        // Show roles
        $roles = $user->roles;
        $this->info("🎭 Roles:");
        foreach ($roles as $role) {
            $this->line("  • {$role->name}");
        }
        $this->newLine();
        
        // Show direct permissions
        $directPermissions = $user->getDirectPermissions();
        $this->info("🔑 Direct Permissions: {$directPermissions->count()}");
        if ($directPermissions->count() > 0) {
            foreach ($directPermissions as $permission) {
                $this->line("  • {$permission->name}");
            }
        } else {
            $this->line("  None");
        }
        $this->newLine();
        
        // Show permissions via roles
        $rolePermissions = $user->getPermissionsViaRoles();
        $this->info("🎯 Permissions via Roles: {$rolePermissions->count()}");
        if ($rolePermissions->count() > 0) {
            $grouped = $rolePermissions->groupBy(function($permission) {
                $parts = explode('_', $permission->name);
                return $parts[count($parts) - 1]; // Get last part (store, user, product, etc.)
            });
            
            foreach ($grouped as $group => $permissions) {
                $this->info("  📁 {$group}: {$permissions->count()}");
                foreach ($permissions as $permission) {
                    $this->line("    • {$permission->name}");
                }
            }
        } else {
            $this->line("  None");
        }
        $this->newLine();
        
        // Show all permissions
        $allPermissions = $user->getAllPermissions();
        $this->info("🌟 Total Permissions: {$allPermissions->count()}");
        
        // Show percentage of total permissions
        $totalSystemPermissions = Permission::count();
        $percentage = round(($allPermissions->count() / $totalSystemPermissions) * 100, 1);
        $this->info("📊 Coverage: {$percentage}% of system permissions");
        
        // Test some key permissions
        $this->newLine();
        $this->info("🧪 Key Permission Tests:");
        
        $testPermissions = [
            'view_all_stores',
            'manage_stores',
            'view_any_store',
            'super_admin_access',
            'bypass_store_restrictions',
            'access_all_stores',
            'view_products',
            'manage_products',
        ];
        
        foreach ($testPermissions as $permission) {
            $hasPermission = $user->can($permission);
            $icon = $hasPermission ? '✅' : '❌';
            $this->line("  {$icon} {$permission}");
        }
        
        return Command::SUCCESS;
    }
}
