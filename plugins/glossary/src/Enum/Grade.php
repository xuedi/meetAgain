<?php declare(strict_types=1);

namespace Plugin\Glossary\Enum;

enum Grade: int
{
    case Again = 1;
    case Hard = 2;
    case Good = 3;
    case Easy = 4;

    public function label(): string
    {
        return match ($this) {
            self::Again => 'glossary_trainer.grade_again',
            self::Hard => 'glossary_trainer.grade_hard',
            self::Good => 'glossary_trainer.grade_good',
            self::Easy => 'glossary_trainer.grade_easy',
        };
    }

    public function isPass(): bool
    {
        return $this !== self::Again;
    }
}
