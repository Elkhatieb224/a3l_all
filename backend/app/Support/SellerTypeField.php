<?php

namespace App\Support;

class SellerTypeField
{
    public const FIELD_ID = 'seller_type';

    public const OWNER_AR = 'مالك';

    public static function isField(array $field): bool
    {
        return ($field['id'] ?? null) === self::FIELD_ID;
    }

    /**
     * @return array{ar?: string, en?: string, tr?: string}|null
     */
    public static function findOwnerOption(array $field): ?array
    {
        foreach ($field['options'] ?? [] as $option) {
            if (is_array($option) && ($option['ar'] ?? null) === self::OWNER_AR) {
                return $option;
            }
            if (is_string($option) && $option === self::OWNER_AR) {
                return ['ar' => $option, 'en' => $option, 'tr' => $option];
            }
        }

        return null;
    }

    public static function ownerStoredValue(array $field, ?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $owner = self::findOwnerOption($field);
        if ($owner === null) {
            return self::OWNER_AR;
        }

        return (string) ($owner[$locale] ?? $owner['ar'] ?? self::OWNER_AR);
    }

    /**
     * Force seller_type to "مالك" when the user is not verified.
     *
     * @param  array<string, mixed>  $customFields
     * @param  array<int, array<string, mixed>>  $schema
     * @return array<string, mixed>
     */
    public static function applyLockedOwner(array $customFields, array $schema, $user, ?string $locale = null): array
    {
        if ($user && ($user->is_verified ?? false)) {
            return $customFields;
        }

        foreach ($schema as $field) {
            if (! self::isField($field)) {
                continue;
            }
            $customFields[self::FIELD_ID] = self::ownerStoredValue($field, $locale);

            break;
        }

        return $customFields;
    }
}
