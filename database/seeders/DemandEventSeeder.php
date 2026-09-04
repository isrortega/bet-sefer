<?php

namespace Database\Seeders;

use App\Models\DemandEvent;
use App\Models\Edition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemandEventSeeder extends Seeder
{
    public function run(): void
    {
        $types = ['unavailable_hit', 'search_miss', 'public_lookup_unavailable', 'acquisition_suggestion'];
        $editions = Edition::query()->pluck('id')->all();
        $isbns = Edition::query()->pluck('isbn_13')->filter()->all();

        for ($i = 0; $i < 24; $i++) {
            $type = $types[array_rand($types)];

            DemandEvent::create([
                'type' => $type,
                'edition_id' => in_array($type, ['unavailable_hit', 'public_lookup_unavailable']) && $editions !== [] ? $editions[array_rand($editions)] : null,
                'isbn' => $type === 'search_miss' && $isbns !== [] ? $isbns[array_rand($isbns)] : null,
                'query_text' => $type === 'search_miss' ? Str::words('history of the roman empire quantum mechanics colombian literature', 3) : null,
                'user_id' => null,
                'ip_hash' => hash('sha256', 'seeded-'.Str::random(6)),
                'created_at' => now()->subDays(rand(0, 150)),
            ]);
        }
    }
}
