<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\User;
use App\Support\CrockfordCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoUsersSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'DemoPassword-2026';

    public function run(): void
    {
        $key = config('app.key');

        $users = [
            'administrator' => [
                'name' => 'Amina Hernández',
                'email' => 'admin@betsefer.local',
                'role' => 'administrator',
            ],
            'librarian' => [
                'name' => 'Isabella Torres',
                'email' => 'librarian@betsefer.local',
                'role' => 'librarian',
            ],
            'shelver' => [
                'name' => 'Mateo Ríos',
                'email' => 'shelver@betsefer.local',
                'role' => 'shelver',
            ],
            'reader' => [
                'name' => 'Ana Ríos',
                'email' => 'reader@betsefer.local',
                'role' => 'reader',
            ],
        ];

        $admin = null;

        foreach ($users as $keyName => $data) {
            $documentNumber = match ($keyName) {
                'administrator' => '1010101010',
                'librarian' => '2020202020',
                'shelver' => '3030303030',
                default => '4040404040',
            };
            $normalised = strtoupper(preg_replace('/[^A-Z0-9]/', '', $documentNumber) ?? $documentNumber);

            $user = User::create([
                'ulid' => (string) Str::ulid(),
                'name' => $data['name'],
                'email' => $data['email'],
                'email_verified_at' => now(),
                'password' => Hash::make(self::DEMO_PASSWORD),
                'status' => UserStatus::Active,
                'member_code' => CrockfordCode::generate(),
                'document_type' => 'CC',
                'document_number' => $documentNumber,
                'document_hash' => hash_hmac('sha256', $normalised, $key),
                'phone' => '+57 300 000 00'.random_int(10, 99),
                'identity_verified_at' => now(),
                'locale' => 'en',
            ]);

            $user->syncRoles([$data['role']]);

            if ($keyName === 'administrator') {
                $admin = $user;
            }
        }

        User::where('email', 'admin@betsefer.local')->update(['identity_verified_by_id' => $admin->id]);
    }
}
