<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);
        $this->call(LoanPolicySeeder::class);
        $this->call(ScheduleSeeder::class);
        $this->call(TaxonomySeeder::class);
        $this->call(DemoUsersSeeder::class);
        $this->call(CatalogueSeeder::class);
        $this->call(LoanHistorySeeder::class);
        $this->call(DemandEventSeeder::class);
    }
}
