<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $prenom = fake()->firstName();
        $nom    = fake()->lastName();

        return [
            'prenom'   => $prenom,
            'nom'      => $nom,
            'username' => Str::lower(Str::slug($prenom, '') . '.' . Str::slug($nom, '')) . fake()->unique()->numberBetween(1, 99999),
            'email'    => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role'     => 'user',
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }
}
