<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Self-registration is disabled, so extra librarian accounts are added here:
 *   make artisan cmd="library:librarian"
 *   make prod-artisan cmd="library:librarian"
 */
class CreateLibrarianCommand extends Command
{
    protected $signature = 'library:librarian
        {email? : Login e-mail}
        {--name= : Display name}';

    protected $description = 'Create a librarian account (or reset its password)';

    public function handle(): int
    {
        $email = $this->argument('email') ?? text('И-мэйл', required: true);
        $existing = User::query()->where('email', $email)->first();
        $name = $this->option('name') ?? ($existing !== null ? $existing->name : text('Нэр', required: true));
        $secret = password('Нууц үг', required: true);

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $secret],
            [
                'email' => ['required', 'email', 'max:255'],
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', Password::default()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => $secret],
        );
        $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();

        $this->info($user->wasRecentlyCreated
            ? "Номын санч {$email} үүслээ."
            : "{$email} хэрэглэгчийн нууц үг шинэчлэгдлээ.");

        return self::SUCCESS;
    }
}
