<?php declare(strict_types=1);

namespace Plugin\Glossary\Enum;

enum MatchResult: string
{
    case Exact = 'exact';
    case NearMiss = 'near_miss';
    case Wrong = 'wrong';

    public function grade(): Grade
    {
        return match ($this) {
            self::Exact => Grade::Good,
            self::NearMiss => Grade::Hard,
            self::Wrong => Grade::Again,
        };
    }
}
