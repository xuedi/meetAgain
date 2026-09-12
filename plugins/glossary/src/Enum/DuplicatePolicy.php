<?php declare(strict_types=1);

namespace Plugin\Glossary\Enum;

enum DuplicatePolicy: string
{
    case Skip = 'skip';
    case Update = 'update';
    case Create = 'create';

    public function label(): string
    {
        return match ($this) {
            self::Skip => 'glossary_import.duplicate_skip',
            self::Update => 'glossary_import.duplicate_update',
            self::Create => 'glossary_import.duplicate_create',
        };
    }
}
