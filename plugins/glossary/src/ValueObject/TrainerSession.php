<?php declare(strict_types=1);

namespace Plugin\Glossary\ValueObject;

use Plugin\Glossary\Enum\AnswerMode;
use Plugin\Glossary\Enum\Direction;
use Plugin\Glossary\Enum\Mode;
use Plugin\Glossary\Enum\Scope;

final readonly class TrainerSession
{
    /**
     * @param list<int> $tagIds
     * @param list<int> $queue
     * @param list<int> $missed
     * @param array{verdict: string, prompt: string, expected: string, given: ?string}|null $feedback
     */
    public function __construct(
        public Scope $scope,
        public array $tagIds,
        public Mode $mode,
        public Direction $direction,
        public AnswerMode $answerMode,
        public array $queue,
        public int $seed,
        public int $total,
        public int $answered = 0,
        public int $correct = 0,
        public array $missed = [],
        public ?array $feedback = null,
    ) {}

    public function isFinished(): bool
    {
        return $this->queue === [];
    }

    /** @param array{verdict: string, prompt: string, expected: string, given: ?string}|null $feedback */
    public function withAnswer(int $glossaryId, bool $correct, ?array $feedback): self
    {
        return new self(
            $this->scope,
            $this->tagIds,
            $this->mode,
            $this->direction,
            $this->answerMode,
            $this->queueAfter($glossaryId),
            $this->seed,
            $this->total,
            $this->answered + 1,
            $this->correct + ($correct ? 1 : 0),
            $correct ? $this->missed : [...$this->missed, $glossaryId],
            $feedback,
        );
    }

    public function withSkipped(int $glossaryId): self
    {
        return new self(
            $this->scope,
            $this->tagIds,
            $this->mode,
            $this->direction,
            $this->answerMode,
            $this->queueAfter($glossaryId),
            $this->seed,
            max($this->answered, $this->total - 1),
            $this->answered,
            $this->correct,
            $this->missed,
            null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'scope' => $this->scope->value,
            'tagIds' => $this->tagIds,
            'mode' => $this->mode->value,
            'direction' => $this->direction->value,
            'answerMode' => $this->answerMode->value,
            'queue' => $this->queue,
            'seed' => $this->seed,
            'total' => $this->total,
            'answered' => $this->answered,
            'correct' => $this->correct,
            'missed' => $this->missed,
            'feedback' => $this->feedback,
        ];
    }

    public static function fromArray(mixed $data): ?self
    {
        if (!is_array($data)) {
            return null;
        }

        $scope = Scope::tryFrom((string) ($data['scope'] ?? ''));
        $mode = Mode::tryFrom((string) ($data['mode'] ?? ''));
        $direction = Direction::tryFrom((string) ($data['direction'] ?? ''));
        $answerMode = AnswerMode::tryFrom((string) ($data['answerMode'] ?? ''));
        if ($scope === null || $mode === null || $direction === null || $answerMode === null) {
            return null;
        }

        return new self(
            $scope,
            self::ids($data['tagIds'] ?? []),
            $mode,
            $direction,
            $answerMode,
            self::ids($data['queue'] ?? []),
            (int) ($data['seed'] ?? 1),
            (int) ($data['total'] ?? 0),
            (int) ($data['answered'] ?? 0),
            (int) ($data['correct'] ?? 0),
            self::ids($data['missed'] ?? []),
            self::feedback($data['feedback'] ?? null),
        );
    }

    /** @return array{verdict: string, prompt: string, expected: string, given: ?string}|null */
    private static function feedback(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $given = $value['given'] ?? null;

        return [
            'verdict' => (string) ($value['verdict'] ?? ''),
            'prompt' => (string) ($value['prompt'] ?? ''),
            'expected' => (string) ($value['expected'] ?? ''),
            'given' => $given === null ? null : (string) $given,
        ];
    }

    /** @return list<int> */
    private function queueAfter(int $glossaryId): array
    {
        $position = array_search($glossaryId, $this->queue, true);

        return $position === false ? $this->queue : array_values(array_slice($this->queue, $position + 1));
    }

    /** @return list<int> */
    private static function ids(mixed $value): array
    {
        return is_array($value) ? array_values(array_map(intval(...), $value)) : [];
    }
}
