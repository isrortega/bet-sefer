<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Shared tree logic for the taxonomy models (Category / Location): id-based
 * paths, subtree reindexing after a move, and leaf-only deletion.
 */
final class TreeEditor
{
    public static function makeSlug(string $name): string
    {
        $slug = Str::slug($name);

        return $slug !== '' ? $slug : Str::lower(Str::random(6));
    }

    /**
     * @param  array{name?: string, slug?: string, code?: string, type?: string|null, parent_id?: int|null}  $attributes
     */
    public static function create(Model $model, array $attributes): Model
    {
        $name = $attributes['name'];
        $parentId = $attributes['parent_id'] ?? null;
        $parent = $parentId !== null ? $model::find($parentId) : null;
        $slug = $attributes['slug'] ?? null;
        if ($slug === null || trim($slug) === '') {
            $slug = self::makeSlug($name);
        }

        $instance = $model::create([
            'ulid' => (string) Str::ulid(),
            'name' => $name,
            'slug' => $slug,
            'code' => $attributes['code'] ?? null,
            'type' => $attributes['type'] ?? null,
            'parent_id' => $parent?->id,
            'path' => '/',
            'depth' => 0,
        ]);

        if ($instance->isFillable('code') && ! empty($attributes['code'])) {
            $instance->code = $attributes['code'];
        }

        $instance->path = ($parent !== null ? $parent->path : '/').$instance->id.'/';
        $instance->depth = $parent !== null ? $parent->depth + 1 : 0;
        $instance->save();

        return $instance;
    }

    /**
     * @param  array{name?: string, slug?: string, code?: string, parent_id?: int|null}  $attributes
     */
    public static function update(Model $model, array $attributes): Model
    {
        $parentChanged = array_key_exists('parent_id', $attributes)
            && (int) ($attributes['parent_id'] ?? 0) !== (int) ($model->parent_id ?? 0);

        if ($parentChanged) {
            $parentId = $attributes['parent_id'];
            $parent = $parentId !== null ? $model::find($parentId) : null;

            for ($cursor = $parent; $cursor !== null; $cursor = $cursor->parent) {
                if ($cursor->is($model)) {
                    throw ValidationException::withMessages(['parent_id' => 'A node cannot be its own descendant.']);
                }
            }
        }

        if (isset($attributes['name'])) {
            $model->name = $attributes['name'];
        }
        if (array_key_exists('slug', $attributes) && trim((string) $attributes['slug']) !== '') {
            $model->slug = $attributes['slug'];
        }
        if ($model->isFillable('code') && isset($attributes['code'])) {
            $model->code = $attributes['code'];
        }

        $model->save();

        if ($parentChanged) {
            $parentId = $attributes['parent_id'];
            $parent = $parentId !== null ? $model::find($parentId) : null;
            $model->parent_id = $parent?->id;
            $model->path = ($parent !== null ? $parent->path : '/').$model->id.'/';
            $model->depth = $parent !== null ? $parent->depth + 1 : 0;
            $model->save();
            self::reindexSubtree($model);
        }

        return $model;
    }

    public static function assertLeaf(Model $model): void
    {
        if ($model->children()->exists()) {
            throw ValidationException::withMessages([
                'node' => 'Delete its children first.',
            ]);
        }
    }

    /** Update child paths/depths under a node that just moved. */
    private static function reindexSubtree(Model $node): void
    {
        foreach ($node->children as $child) {
            $child->path = $node->path.$child->id.'/';
            $child->depth = $node->depth + 1;
            $child->save();
            self::reindexSubtree($child);
        }
    }
}
