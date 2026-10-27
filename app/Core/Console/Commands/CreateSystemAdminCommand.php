<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Models\HouseholdMember;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

final class CreateSystemAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'core:create-admin
                            {--name= : The name of the system administrator}
                            {--email= : The email address of the system administrator}
                            {--password= : The password for the system administrator}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Provision or promote a user to system administrator';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = (string) ($this->option('name') ?? $this->ask('Name'));
        $email = (string) ($this->option('email') ?? $this->ask('Email address'));
        $password = (string) ($this->option('password') ?? $this->secret('Password (minimum 8 characters)'));

        $validator = Validator::make([
            'name' => trim($name),
            'email' => mb_strtolower(trim($email)),
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $validated = $validator->validated();
        $email = (string) $validated['email'];

        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null) {
            $isMember = HouseholdMember::query()->where('user_id', $existing->getKey())->exists();

            if ($isMember) {
                $this->error(__('household.admin_not_member'));

                return self::FAILURE;
            }

            $existing->is_system_admin = true;
            $existing->save();

            $this->info("User [{$email}] is now a system administrator.");

            return self::SUCCESS;
        }

        $user = new User([
            'name' => $validated['name'],
            'email' => $email,
            'password' => $validated['password'],
        ]);
        $user->is_system_admin = true;
        $user->save();

        $this->info("System administrator [{$email}] created successfully.");

        return self::SUCCESS;
    }
}
