<?php declare(strict_types=1);

namespace Plugin\Glossary\Service;

use Plugin\Glossary\Enum\MatchResult;

final readonly class AnswerMatcher
{
    private const int NEAR_MISS_MIN_LENGTH = 3;

    public function match(string $given, string $expected, bool $foldDiacritics = true): MatchResult
    {
        $answer = $this->normalize($given);
        if ($answer === '') {
            return MatchResult::Wrong;
        }

        $result = MatchResult::Wrong;
        foreach ($this->alternatives($expected) as $alternative) {
            if ($answer === $alternative) {
                return MatchResult::Exact;
            }

            $left = $foldDiacritics ? $this->fold($answer) : $answer;
            $right = $foldDiacritics ? $this->fold($alternative) : $alternative;
            $longEnough = mb_strlen($right) >= self::NEAR_MISS_MIN_LENGTH;
            if ($left === $right || $longEnough && $this->distance($left, $right) === 1) {
                $result = MatchResult::NearMiss;
            }
        }

        return $result;
    }

    public function same(string $left, string $right): bool
    {
        return $this->normalize($left) === $this->normalize($right);
    }

    /** @return list<string> */
    private function alternatives(string $expected): array
    {
        $parts = preg_split('~[;/]~u', $expected) ?: [];

        return array_values(array_filter(array_map($this->normalize(...), $parts), static fn(string $part): bool => $part !== ''));
    }

    private function normalize(string $text): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $text)));
    }

    private function fold(string $text): string
    {
        return (string) transliterator_transliterate('NFD; [:Nonspacing Mark:] Remove; NFC', $text);
    }

    private function distance(string $left, string $right): int
    {
        $a = mb_str_split($left);
        $b = mb_str_split($right);
        if (abs(count($a) - count($b)) > 1) {
            return 2;
        }

        $previous = range(0, count($b));
        foreach ($a as $i => $charA) {
            $current = [$i + 1];
            foreach ($b as $j => $charB) {
                $current[] = min($previous[$j + 1] + 1, $current[$j] + 1, $previous[$j] + ($charA === $charB ? 0 : 1));
            }
            $previous = $current;
        }

        return $previous[count($b)];
    }
}
