<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the first librarian account from ADMIN_* settings. Safe to run
 * more than once: an existing account is left untouched.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array{name: string, email: string, password: string} $admin */
        $admin = config('library.admin');

        $user = User::query()->firstOrCreate(
            ['email' => $admin['email']],
            ['name' => $admin['name'], 'password' => $admin['password']],
        );

        if ($user->wasRecentlyCreated) {
            $user->forceFill(['email_verified_at' => now()])->save();
            $this->command?->info("Librarian account created: {$admin['email']}");
        }
    }
}
