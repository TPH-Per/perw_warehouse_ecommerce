<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

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
    protected $description = 'Reset admin password';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = \App\Models\User::where('email', 'admin@perw.com')->first();

        if ($user) {
            $user->password = Hash::make('password');
            $user->save();

            $this->info('Admin password reset successfully!');
            $this->info('Email: admin@perw.com');
            $this->info('Password: password');
        } else {
            $this->error('Admin user not found!');
        }
    }
}
