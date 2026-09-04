<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategories();
        $this->seedLocations();
    }

    private function seedCategories(): void
    {
        Category::query()->delete();

        $roots = [
            'fiction' => ['Fiction', [
                'sci-fi' => ['Science fiction', []],
                'fantasy' => ['Fantasy', []],
                'mystery' => ['Mystery & Thriller', []],
                'literary' => ['Literary fiction', []],
            ]],
            'non-fiction' => ['Non-fiction', [
                'history' => ['History', []],
                'biography' => ['Biography', []],
                'science' => ['Science', [
                    'physics' => ['Physics', []],
                    'computer' => ['Computer science', []],
                    'biology' => ['Biology', []],
                ]],
                'society' => ['Society & ideas', []],
            ]],
            'reference' => ['Reference', []],
            'children' => ['Children & young adult', []],
        ];

        foreach ($roots as $slug => [$name, $children]) {
            $this->createCategory($name, $slug, null, 0, '/', $children);
        }
    }

    /**
     * @param  array<string, array{0: string, 1: array}>  $children
     */
    private function createCategory(string $name, string $slug, ?int $parentId, int $depth, string $parentPath, array $children): void
    {
        $category = Category::create([
            'ulid' => (string) Str::ulid(),
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => $slug,
            'path' => '/',
            'depth' => $depth,
        ]);

        $category->forceFill(['path' => $parentPath.$category->id.'/'])->save();

        foreach ($children as $childSlug => [$childName, $grandchildren]) {
            $this->createCategory($childName, $childSlug, $category->id, $depth + 1, $category->path, $grandchildren);
        }
    }

    private function seedLocations(): void
    {
        Location::query()->delete();

        $floor1 = $this->createLocation('Floor 1', 'F1', 'floor', null, 0, '/');

        $roomGeneral = $this->createLocation('General collection', 'F1-A', 'room', $floor1->id, 1, $floor1->path);
        foreach (['1', '2'] as $aisle) {
            $aisleRow = $this->createLocation("Aisle {$aisle}", "F1-A{$aisle}", 'aisle', $roomGeneral->id, 2, $roomGeneral->path);
            foreach (['1', '2', '3'] as $shelf) {
                $this->createLocation("Shelf {$shelf}", "F1-A{$aisle}-{$shelf}", 'shelf', $aisleRow->id, 3, $aisleRow->path);
            }
        }

        $roomReference = $this->createLocation('Reference corner', 'F1-B', 'room', $floor1->id, 1, $floor1->path);
        $refAisle = $this->createLocation('Aisle 1', 'F1-B1', 'aisle', $roomReference->id, 2, $roomReference->path);
        foreach (['1', '2'] as $shelf) {
            $this->createLocation("Shelf {$shelf}", "F1-B1-{$shelf}", 'shelf', $refAisle->id, 3, $refAisle->path);
        }

        $floor2 = $this->createLocation('Floor 2', 'F2', 'floor', null, 0, '/');
        $roomMedia = $this->createLocation('Periodicals & media', 'F2-A', 'room', $floor2->id, 1, $floor2->path);
        $mediaAisle = $this->createLocation('Aisle 1', 'F2-A1', 'aisle', $roomMedia->id, 2, $roomMedia->path);
        foreach (['1', '2'] as $shelf) {
            $this->createLocation("Shelf {$shelf}", "F2-A1-{$shelf}", 'shelf', $mediaAisle->id, 3, $mediaAisle->path);
        }

        $roomYA = $this->createLocation('Young adults', 'F2-B', 'room', $floor2->id, 1, $floor2->path);
        $yaAisle = $this->createLocation('Aisle 1', 'F2-B1', 'aisle', $roomYA->id, 2, $roomYA->path);
        foreach (['1', '2', '3'] as $shelf) {
            $this->createLocation("Shelf {$shelf}", "F2-B1-{$shelf}", 'shelf', $yaAisle->id, 3, $yaAisle->path);
        }
    }

    private function createLocation(string $name, string $code, string $type, ?int $parentId, int $depth, string $parentPath): Location
    {
        $location = Location::create([
            'ulid' => (string) Str::ulid(),
            'parent_id' => $parentId,
            'name' => $name,
            'code' => $code,
            'type' => $type,
            'path' => '/',
            'depth' => $depth,
        ]);

        $location->forceFill(['path' => $parentPath.$location->id.'/'])->save();

        return $location;
    }
}
