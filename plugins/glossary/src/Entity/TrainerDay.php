<?php declare(strict_types=1);

namespace Plugin\Glossary\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Plugin\Glossary\Repository\TrainerDayRepository;

#[ORM\Entity(repositoryClass: TrainerDayRepository::class)]
#[ORM\Table(name: 'plg_glossary_trainer_day')]
#[ORM\UniqueConstraint(name: 'uniq_glossary_day_user_day', columns: ['user_id', 'day'])]
class TrainerDay
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $userId;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $day;

    #[ORM\Column]
    private int $reviewed = 0;

    #[ORM\Column]
    private int $correct = 0;

    #[ORM\Column]
    private int $newStarted = 0;

    public function __construct(int $userId, DateTimeImmutable $day)
    {
        $this->userId = $userId;
        $this->day = $day->setTime(0, 0);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getDay(): DateTimeImmutable
    {
        return $this->day;
    }

    public function getReviewed(): int
    {
        return $this->reviewed;
    }

    public function getCorrect(): int
    {
        return $this->correct;
    }

    public function getNewStarted(): int
    {
        return $this->newStarted;
    }

    public function record(bool $correct, bool $startedNew): static
    {
        ++$this->reviewed;
        if ($correct) {
            ++$this->correct;
        }
        if ($startedNew) {
            ++$this->newStarted;
        }

        return $this;
    }
}
