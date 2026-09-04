<?php

namespace Database\Seeders;

use App\Enums\CopyStatus;
use App\Models\Copy;
use App\Models\Loan;
use App\Models\User;
use App\Support\CrockfordCode;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LoanHistorySeeder extends Seeder
{
    public function run(): void
    {
        $readers = $this->readers();
        $librarian = User::where('email', 'librarian@betsefer.local')->first() ?? User::role('librarian')->first();

        $copies = Copy::query()
            ->whereIn('status', ['available'])
            ->whereHas('edition', fn ($q) => $q->where('loan_type', 'general'))
            ->get()
            ->shuffle();

        $active = [];
        $now = CarbonImmutable::now();

        foreach ($copies->take(120) as $index => $copy) {
            $reader = $readers->random();
            $roll = mt_rand(1, 100);

            $termDays = collect([7, 10, 10, 14, 21])->random();
            $hours = $termDays * 24;

            // 12% active + overdue, 10% active (still on loan), rest returned.
            if ($roll <= 12) {
                $dueAt = $now->subDays(mt_rand(2, 18));
                $checkedOutAt = $dueAt->subDays($termDays);
                $returnedAt = null;
            } elseif ($roll <= 22) {
                $dueAt = $now->addDays(mt_rand(3, 9));
                $checkedOutAt = $dueAt->subDays($termDays);
                $returnedAt = null;
            } else {
                $checkedOutAt = $now->subDays(mt_rand(5, 175));
                $dueAt = $checkedOutAt->addDays($termDays);
                $lentFull = mt_rand(0, 1) === 1;
                $returnedAt = $lentFull
                    ? $dueAt->subHours(mt_rand(1, 60))
                    : $checkedOutAt->addDays(mt_rand(1, max(1, $termDays - 1)));
            }

            $loan = Loan::create([
                'ulid' => (string) Str::ulid(),
                'code' => CrockfordCode::withPrefix('LN'),
                'copy_id' => $copy->id,
                'user_id' => $reader->id,
                'checked_out_by_id' => $librarian->id,
                'checked_in_by_id' => $returnedAt !== null ? $librarian->id : null,
                'checked_out_at' => $checkedOutAt,
                'due_at' => $dueAt,
                'returned_at' => $returnedAt,
                'renewals_count' => 0,
                'policy_snapshot' => [
                    'loan_type' => 'general',
                    'hours' => $hours,
                    'min_hours' => 168,
                    'max_hours' => 360,
                    'renewals_allowed' => 2,
                ],
            ]);

            if ($returnedAt === null) {
                $copy->forceFill([
                    'status' => CopyStatus::OnLoan,
                    'status_changed_at' => $checkedOutAt,
                ])->save();
            }
        }

        // A few returned copies are sitting at reception / in transit for the shelver queue.
        $queueTargets = Copy::where('status', CopyStatus::Available)->get()->shuffle();
        foreach ($queueTargets->take(6) as $copy) {
            $copy->forceFill(['status' => CopyStatus::InTransit, 'status_changed_at' => now()->subHours(mt_rand(1, 30))])->save();
        }
        foreach ($queueTargets->skip(6)->take(4) as $copy) {
            $copy->forceFill(['status' => CopyStatus::AtReception, 'status_changed_at' => now()->subMinutes(mt_rand(20, 200))])->save();
        }
    }

    /** @return Collection<int, User> */
    private function readers(): Collection
    {
        $pool = collect();
        $names = ['Lucía Morales', 'Diego Castro', 'Valentina Ortiz', 'Santiago Peña', 'Camila Rojas', 'Tomás Aguirre', 'Sofía Londoño', 'Julián Carmona', 'Mariana Vidal', 'Andrés Quintero'];

        foreach ($names as $name) {
            $email = Str::slug($name).'@betsefer.local';
            $pool->push(User::firstOrCreate(
                ['email' => $email],
                [
                    'ulid' => (string) Str::ulid(),
                    'name' => $name,
                    'email_verified_at' => now(),
                    'password' => Hash::make(DemoUsersSeeder::DEMO_PASSWORD),
                    'status' => 'active',
                    'member_code' => CrockfordCode::generate(),
                    'document_type' => 'CC',
                    'document_hash' => hash_hmac('sha256', (string) mt_rand(1000000000, 9999999999), config('app.key')),
                    'identity_verified_at' => now(),
                    'locale' => 'en',
                ],
            ));
        }

        $reader = User::where('email', 'reader@betsefer.local')->first();
        if ($reader !== null) {
            $pool->push($reader);
        }

        $pool->each(fn (User $user) => $user->syncRoles(['reader']));

        return $pool;
    }
}
