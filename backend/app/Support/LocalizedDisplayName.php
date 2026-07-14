<?php

namespace App\Support;

class LocalizedDisplayName
{
    public static function format(string $name, ?string $locale = null): string
    {
        $name = trim($name);
        if ($name === '') {
            return $name;
        }

        $locale = $locale ?? app()->getLocale();

        if (! self::shouldCapitalize($name, $locale)) {
            return $name;
        }

        return self::capitalizeFirstLetter($name, $locale);
    }

    private static function shouldCapitalize(string $name, string $locale): bool
    {
        if (in_array($locale, ['en', 'tr'], true)) {
            return true;
        }

        return self::isLatinScriptName($name);
    }

    private static function isLatinScriptName(string $name): bool
    {
        if (! preg_match('/\p{Latin}/u', $name)) {
            return false;
        }

        return (bool) preg_match('/^[\p{Latin}\p{N}\s\-&.\'’]+$/u', $name);
    }

    private static function capitalizeFirstLetter(string $name, string $locale): string
    {
        if (! preg_match('/\p{L}/u', $name, $match, PREG_OFFSET_CAPTURE)) {
            return $name;
        }

        $pos = (int) $match[0][1];
        $char = $match[0][0];
        $encoding = 'UTF-8';
        $upper = $locale === 'tr'
            ? self::turkishUpperFirst($char)
            : mb_strtoupper($char, $encoding);

        return mb_substr($name, 0, $pos, $encoding)
            .$upper
            .mb_substr($name, $pos + mb_strlen($char, $encoding), null, $encoding);
    }

    private static function turkishUpperFirst(string $char): string
    {
        return match ($char) {
            'i' => 'İ',
            'ı' => 'I',
            default => mb_strtoupper($char, 'UTF-8'),
        };
    }
}
