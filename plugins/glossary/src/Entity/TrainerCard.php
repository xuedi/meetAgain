<?php declare(strict_types=1);

namespace Plugin\Glossary\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Plugin\Glossary\Enum\CardState;
use Plugin\Glossary\Enum\Direction;
use Plugin\Glossary\Repository\TrainerCardRepository;

#[ORM\Entity(repositoryClass: TrainerCardRepository::class)]
#[ORM\Table(name: 'plg_glossary_trainer_card')]
#[ORM\UniqueConstraint(name: 'uniq_glossary_card_user_entry_direction', columns: ['user_id', 'glossary_id', 'direction'])]
#[ORM\Index(name: 'idx_glossary_card_due', columns: ['user_id', 'state', 'due_at'])]
class TrainerCard
{
    public const int DEFAULT_EASE = 2500;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $userId;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Glossary $glossary;

    #[ORM\Column(length: 20, enumType: Direction::class)]
    private Direction $direction;

    #[ORM\Column(length: 12, enumType: CardState::class)]
    private CardState $state = CardState::New;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $dueAt = null;

    #[ORM\Column]
    private int $intervalDays = 0;

    #[ORM\Column]
    private int $easePermille = self::DEFAULT_EASE;

    #[ORM\Column]
    private int $repetitions = 0;

    #[ORM\Column]
    private int $lapses = 0;

    #[ORM\Column]
    private int $timesSeen = 0;

    #[ORM\Column]
    private int $timesCorrect = 0;

    #[ORM\Column]
    private bool $marked = false;

    #[ORM\Column]
    private bool $suspended = false;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $lastReviewedAt = null;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    public function __construct(int $userId, Glossary $glossary, Direction $direction, DateTimeImmutable $createdAt)
    {
        $this->userId = $userId;
        $this->glossary = $glossary;
        $this->direction = $direction;
        $this->createdAt = $createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getGlossary(): Glossary
    {
        return $this->glossary;
    }

    public function getDirection(): Direction
    {
        return $this->direction;
    }

    public function getState(): CardState
    {
        return $this->state;
    }

    public function setState(CardState $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getDueAt(): ?DateTimeImmutable
    {
        return $this->dueAt;
    }

    public function setDueAt(?DateTimeImmutable $dueAt): static
    {
        $this->dueAt = $dueAt;

        return $this;
    }

    public function getIntervalDays(): int
    {
        return $this->intervalDays;
    }

    public function setIntervalDays(int $intervalDays): static
    {
        $this->intervalDays = $intervalDays;

        return $this;
    }

    public function getEasePermille(): int
    {
        return $this->easePermille;
    }

    public function setEasePermille(int $easePermille): static
    {
        $this->easePermille = $easePermille;

        return $this;
    }

    public function getRepetitions(): int
    {
        return $this->repetitions;
    }

    public function setRepetitions(int $repetitions): static
    {
        $this->repetitions = $repetitions;

        return $this;
    }

    public function getLapses(): int
    {
        return $this->lapses;
    }

    public function setLapses(int $lapses): static
    {
        $this->lapses = $lapses;

        return $this;
    }

    public function getTimesSeen(): int
    {
        return $this->timesSeen;
    }

    public function getTimesCorrect(): int
    {
        return $this->timesCorrect;
    }

    public function recordAnswer(bool $correct, DateTimeImmutable $at): static
    {
        ++$this->timesSeen;
        if ($correct) {
            ++$this->timesCorrect;
        }
        $this->lastReviewedAt = $at;

        return $this;
    }

    public function isMarked(): bool
    {
        return $this->marked;
    }

    public function setMarked(bool $marked): static
    {
        $this->marked = $marked;

        return $this;
    }

    public function isSuspended(): bool
    {
        return $this->suspended;
    }

    public function setSuspended(bool $suspended): static
    {
        $this->suspended = $suspended;

        return $this;
    }

    public function getLastReviewedAt(): ?DateTimeImmutable
    {
        return $this->lastReviewedAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
