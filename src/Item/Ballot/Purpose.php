<?php declare(strict_types=1);

namespace App\Item\Ballot;

final class Purpose
{
    public const string PREFIX = 'event.item.';
    public const string SUBJECT_TYPE = 'event';

    public static function forType(string $itemType): string
    {
        return self::PREFIX . $itemType;
    }

    public static function itemTypeOf(string $purpose): ?string
    {
        if (!str_starts_with($purpose, self::PREFIX)) {
            return null;
        }

        $itemType = substr($purpose, strlen(self::PREFIX));

        return $itemType === '' ? null : $itemType;
    }
}
