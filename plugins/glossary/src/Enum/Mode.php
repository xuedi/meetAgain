<?php declare(strict_types=1);

namespace Plugin\Glossary\Enum;

enum Mode: string
{
    case Review = 'review';
    case Practice = 'practice';

    public function label(): string
    {
        return match ($this) {
            self::Review => 'glossary_trainer.mode_review',
            self::Practice => 'glossary_trainer.mode_practice',
        };
    }
}
