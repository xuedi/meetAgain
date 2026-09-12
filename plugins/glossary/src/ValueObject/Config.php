<?php declare(strict_types=1);

namespace Plugin\Glossary\ValueObject;

use App\Publisher\PluginSettings\Data;
use Plugin\Glossary\Enum\AnswerMode;
use Plugin\Glossary\Enum\Direction;

final class Config implements Data
{
    public const int SESSION_SIZE_MIN = 5;
    public const int SESSION_SIZE_MAX = 100;
    public const int NEW_CARDS_MAX = 200;

    private bool $secondaryEnabled = false;
    private ?string $secondaryLabel = null;
    private ?string $primaryLabel = null;
    private ?string $definitionLabel = null;
    private ?string $termLanguage = null;
    private bool $trainerEnabled = false;
    private int $sessionSize = 20;
    private int $newCardsPerDay = 10;

    /** @var list<Direction> */
    private array $directions = [Direction::TermToDefinition, Direction::DefinitionToTerm];
    private AnswerMode $defaultAnswerMode = AnswerMode::Flip;
    private bool $leaderboardEnabled = false;

    public function isSecondaryEnabled(): bool
    {
        return $this->secondaryEnabled;
    }

    public function setSecondaryEnabled(bool $secondaryEnabled): static
    {
        $this->secondaryEnabled = $secondaryEnabled;

        return $this;
    }

    public function getSecondaryLabel(): ?string
    {
        return $this->secondaryLabel;
    }

    public function setSecondaryLabel(?string $secondaryLabel): static
    {
        $this->secondaryLabel = self::trimToNull($secondaryLabel);

        return $this;
    }

    public function getPrimaryLabel(): ?string
    {
        return $this->primaryLabel;
    }

    public function setPrimaryLabel(?string $primaryLabel): static
    {
        $this->primaryLabel = self::trimToNull($primaryLabel);

        return $this;
    }

    public function getDefinitionLabel(): ?string
    {
        return $this->definitionLabel;
    }

    public function setDefinitionLabel(?string $definitionLabel): static
    {
        $this->definitionLabel = self::trimToNull($definitionLabel);

        return $this;
    }

    public function getTermLanguage(): ?string
    {
        return $this->termLanguage;
    }

    public function setTermLanguage(?string $termLanguage): static
    {
        $this->termLanguage = self::languageCode($termLanguage);

        return $this;
    }

    public function isTrainerEnabled(): bool
    {
        return $this->trainerEnabled;
    }

    public function setTrainerEnabled(bool $trainerEnabled): static
    {
        $this->trainerEnabled = $trainerEnabled;

        return $this;
    }

    public function getSessionSize(): int
    {
        return $this->sessionSize;
    }

    public function setSessionSize(int $sessionSize): static
    {
        $this->sessionSize = self::clamp($sessionSize, self::SESSION_SIZE_MIN, self::SESSION_SIZE_MAX);

        return $this;
    }

    public function getNewCardsPerDay(): int
    {
        return $this->newCardsPerDay;
    }

    public function setNewCardsPerDay(int $newCardsPerDay): static
    {
        $this->newCardsPerDay = self::clamp($newCardsPerDay, 0, self::NEW_CARDS_MAX);

        return $this;
    }

    /** @return list<Direction> */
    public function getDirections(): array
    {
        return $this->directions;
    }

    /** @param iterable<Direction> $directions */
    public function setDirections(iterable $directions): static
    {
        $chosen = [];
        foreach ($directions as $direction) {
            $chosen[$direction->value] = $direction;
        }
        $this->directions = $chosen === [] ? [Direction::TermToDefinition] : array_values($chosen);

        return $this;
    }

    /** @return list<Direction> the configured directions this entry shape can actually serve */
    public function getOfferedDirections(): array
    {
        $offered = array_values(array_filter(
            $this->directions,
            fn(Direction $direction): bool => $direction !== Direction::SecondaryToTerm || $this->secondaryEnabled,
        ));

        return $offered === [] ? [Direction::TermToDefinition] : $offered;
    }

    public function getDefaultAnswerMode(): AnswerMode
    {
        return $this->defaultAnswerMode;
    }

    public function setDefaultAnswerMode(AnswerMode $defaultAnswerMode): static
    {
        $this->defaultAnswerMode = $defaultAnswerMode;

        return $this;
    }

    public function isLeaderboardEnabled(): bool
    {
        return $this->leaderboardEnabled;
    }

    public function setLeaderboardEnabled(bool $leaderboardEnabled): static
    {
        $this->leaderboardEnabled = $leaderboardEnabled;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'secondaryEnabled' => $this->secondaryEnabled,
            'secondaryLabel' => $this->secondaryLabel,
            'primaryLabel' => $this->primaryLabel,
            'definitionLabel' => $this->definitionLabel,
            'termLanguage' => $this->termLanguage,
            'trainerEnabled' => $this->trainerEnabled,
            'sessionSize' => $this->sessionSize,
            'newCardsPerDay' => $this->newCardsPerDay,
            'directions' => array_map(static fn(Direction $direction): string => $direction->value, $this->directions),
            'defaultAnswerMode' => $this->defaultAnswerMode->value,
            'leaderboardEnabled' => $this->leaderboardEnabled,
        ];
    }

    public static function fromArray(array $raw): static
    {
        $config = new self();
        $config->secondaryEnabled = (bool) ($raw['secondaryEnabled'] ?? false);
        $config->secondaryLabel = self::trimToNull($raw['secondaryLabel'] ?? null);
        $config->primaryLabel = self::trimToNull($raw['primaryLabel'] ?? null);
        $config->definitionLabel = self::trimToNull($raw['definitionLabel'] ?? null);
        $config->termLanguage = self::languageCode($raw['termLanguage'] ?? null);
        $config->trainerEnabled = (bool) ($raw['trainerEnabled'] ?? false);
        $config->setSessionSize((int) ($raw['sessionSize'] ?? $config->sessionSize));
        $config->setNewCardsPerDay((int) ($raw['newCardsPerDay'] ?? $config->newCardsPerDay));
        if (is_array($raw['directions'] ?? null)) {
            $config->setDirections(array_filter(array_map(Direction::tryFrom(...), array_map(strval(...), $raw['directions']))));
        }
        $config->defaultAnswerMode = AnswerMode::tryFrom((string) ($raw['defaultAnswerMode'] ?? '')) ?? AnswerMode::Flip;
        $config->leaderboardEnabled = (bool) ($raw['leaderboardEnabled'] ?? false);

        return $config;
    }

    private static function trimToNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private static function languageCode(?string $value): ?string
    {
        $code = self::trimToNull($value);

        return $code === null ? null : mb_substr(mb_strtolower($code), 0, 5);
    }

    private static function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }
}
