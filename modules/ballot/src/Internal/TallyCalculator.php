<?php declare(strict_types=1);

namespace Module\Ballot\Internal;

final readonly class TallyCalculator
{
    /**
     * @param list<string>       $optionKeys
     * @param array<string, int> $counts
     */
    public function decide(array $optionKeys, array $counts): TallyResult
    {
        $tallied = [];
        foreach ($optionKeys as $key) {
            $tallied[$key] = $counts[$key] ?? 0;
        }

        if ($tallied === [] || array_sum($tallied) === 0) {
            return new TallyResult();
        }

        $highest = max($tallied);
        $leaders = array_keys($tallied, $highest, true);

        return count($leaders) === 1 ? new TallyResult($leaders[0]) : new TallyResult(null, $leaders);
    }
}
