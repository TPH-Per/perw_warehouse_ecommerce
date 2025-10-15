<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class CheckAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-admin-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check admin user details';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = \App\Models\User::where('email', 'admin@perw.com')->first();

        if ($user) {
            $this->info('Admin user found:');
            $this->info('Name: ' . $user->full_name);
            $this->info('Email: ' . $user->email);
            $this->info('Role: ' . $user->role->name);
            $this->info('Password Hash: ' . $user->password_hash);

            // Check if it's a bcrypt hash
            if (strpos($user->password_hash, '$2y$') === 0) {
                $this->info('Password hash type: Bcrypt');
            } else {
                $this->error('Password hash type: NOT Bcrypt');
            }

            // Test password validation
            if (Hash::check('password', $user->password_hash)) {
                $this->info('Password validation: SUCCESS');
            } else {
                $this->error('Password validation: FAILED');

                // Reset password
                $this->info('Resetting password...');
                $user->password_hash = Hash::make('password');
                $user->save();
                $this->info('Password reset completed');
            }

            // Test authentication
            if (Auth::attempt(['email' => 'admin@perw.com', 'password' => 'password'])) {
                $this->info('Authentication test: SUCCESS');
                Auth::logout();
            } else {
                $this->error('Authentication test: FAILED');
            }
        } else {
            $this->error('Admin user not found');
        }
    }
}
