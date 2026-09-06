<?php declare(strict_types=1);

namespace Module\Ballot\Internal\Entity;

use Doctrine\ORM\Mapping as ORM;
use Module\Ballot\Internal\Repository\BallotOptionRepository;

#[ORM\Entity(repositoryClass: BallotOptionRepository::class)]
#[ORM\Table(name: 'mod_ballot_option')]
#[ORM\UniqueConstraint(name: 'uniq_ballot_option_key', columns: ['ballot_id', 'option_key'])]
class BallotOption
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Ballot::class, inversedBy: 'options')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Ballot $ballot;

    #[ORM\Column(length: 191)]
    private string $optionKey;

    #[ORM\Column(length: 255)]
    private string $label;

    #[ORM\Column]
    private int $position;

    public function __construct(Ballot $ballot, string $optionKey, string $label, int $position)
    {
        $this->ballot = $ballot;
        $this->optionKey = $optionKey;
        $this->label = $label;
        $this->position = $position;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBallot(): Ballot
    {
        return $this->ballot;
    }

    public function getOptionKey(): string
    {
        return $this->optionKey;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}
