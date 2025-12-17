<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->createRoles();

        $user = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@topntech.com',
            'password' => Hash::make('root1234'),
            'status' => true,
        ]);

        $user->assignRole('Admin');
    }

    private function createRoles(): void
    {
        $roles = [
            'Admin',
            'User',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }
}
