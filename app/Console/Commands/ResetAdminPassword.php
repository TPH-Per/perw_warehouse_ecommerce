<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\User;

class ResetAdminPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-admin-password';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset admin password or create admin user if not exists';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Ensure admin and manager roles exist
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrator with full access']
        );

        $managerRole = Role::firstOrCreate(
            ['name' => 'manager'],
            ['description' => 'Inventory manager with limited access']
        );

        // Find or create admin user
        $user = User::firstOrCreate(
            ['email' => 'admin@perw.com'],
            [
                'name' => 'Admin User',
                'full_name' => 'Admin User',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
                'status' => 'active',
                'phone_number' => '0123456789'
            ]
        );

        // If user already existed, just update the password
        if (!$user->wasRecentlyCreated) {
            $user->password = Hash::make('password');
            $user->save();

            $this->info('Admin password reset successfully!');
        } else {
            $this->info('Admin user created successfully!');
        }

        $this->info('Email: admin@perw.com');
        $this->info('Password: password');
        $this->info('Role: ' . $user->role->name);
    }
}
