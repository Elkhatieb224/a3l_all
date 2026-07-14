<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Subcategory;

class CustomFieldsResolver
{
    /**
     * @return array{fields: array<int, array<string, mixed>>, source: ?string, source_subcategory_id: ?int, source_category_id: ?int}
     */
    public static function resolve(?Category $category, ?Subcategory $leafSubcategory): array
    {
        if ($leafSubcategory) {
            foreach (self::ancestorChainFromLeaf($leafSubcategory) as $node) {
                if (self::hasDefinedFields($node->custom_fields)) {
                    return [
                        'fields' => is_array($node->custom_fields) ? $node->custom_fields : [],
                        'source' => 'subcategory',
                        'source_subcategory_id' => (int) $node->id,
                        'source_category_id' => null,
                    ];
                }
            }
        }

        if ($category && self::hasDefinedFields($category->custom_fields)) {
            return [
                'fields' => is_array($category->custom_fields) ? $category->custom_fields : [],
                'source' => 'category',
                'source_subcategory_id' => null,
                'source_category_id' => (int) $category->id,
            ];
        }

        return [
            'fields' => [],
            'source' => null,
            'source_subcategory_id' => null,
            'source_category_id' => null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function resolveActiveFields(?Category $category, ?Subcategory $leafSubcategory): array
    {
        $resolved = self::resolve($category, $leafSubcategory);

        return self::onlyActive($resolved['fields']);
    }

    /**
     * Leaf first, then parents up to the category root subcategory level.
     *
     * @return list<Subcategory>
     */
    public static function ancestorChainFromLeaf(Subcategory $leaf): array
    {
        $chain = [$leaf];
        $parentId = $leaf->parent_subcategory_id;

        while ($parentId) {
            $parent = Subcategory::query()->find($parentId);
            if (! $parent) {
                break;
            }
            $chain[] = $parent;
            $parentId = $parent->parent_subcategory_id;
        }

        return $chain;
    }

    public static function hasDefinedFields(mixed $fields): bool
    {
        if (! is_array($fields) || $fields === []) {
            return false;
        }

        foreach ($fields as $field) {
            if (is_array($field) && ($field['id'] ?? '') !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<int, array<string, mixed>>
     */
    public static function onlyActive(array $fields): array
    {
        return array_values(array_filter($fields, function ($field) {
            return is_array($field) && ($field['is_active'] ?? true);
        }));
    }

    /**
     * Only one subcategory in a root→leaf path may define fields.
     */
    public static function pathConflictMessage(Subcategory $subcategory): ?string
    {
        $chain = self::ancestorChainFromLeaf($subcategory);

        for ($i = 1, $c = count($chain); $i < $c; $i++) {
            if (self::hasDefinedFields($chain[$i]->custom_fields)) {
                return __('admin.categories.custom_fields.path_ancestor_conflict', [
                    'name' => $chain[$i]->name_ar,
                ]);
            }
        }

        $descendantIds = $subcategory->getDescendantIds();
        $descendantIds = array_values(array_filter(
            $descendantIds,
            fn ($id) => (int) $id !== (int) $subcategory->id
        ));

        if ($descendantIds === []) {
            return null;
        }

        $blocking = Subcategory::query()
            ->whereIn('id', $descendantIds)
            ->get(['id', 'name_ar', 'custom_fields'])
            ->first(fn (Subcategory $row) => self::hasDefinedFields($row->custom_fields));

        if ($blocking) {
            return __('admin.categories.custom_fields.path_descendant_conflict', [
                'name' => $blocking->name_ar,
            ]);
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $schema
     */
    public static function findFieldById(array $schema, string $fieldId): ?array
    {
        foreach ($schema as $field) {
            if (is_array($field) && ($field['id'] ?? '') === $fieldId) {
                return $field;
            }
        }

        return null;
    }
}
