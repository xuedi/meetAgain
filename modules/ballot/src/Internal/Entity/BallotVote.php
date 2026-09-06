<?php declare(strict_types=1);

namespace Module\Ballot\Internal\Entity;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Module\Ballot\Internal\Repository\BallotVoteRepository;

#[ORM\Entity(repositoryClass: BallotVoteRepository::class)]
#[ORM\Table(name: 'mod_ballot_vote')]
#[ORM\UniqueConstraint(name: 'uniq_ballot_vote_choice', columns: ['ballot_id', 'user_id', 'option_key'])]
#[ORM\Index(name: 'idx_ballot_vote_voter', columns: ['ballot_id', 'user_id'])]
class BallotVote
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Ballot::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Ballot $ballot;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 191)]
    private string $optionKey;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    public function __construct(Ballot $ballot, User $user, string $optionKey, DateTimeImmutable $now)
    {
        $this->ballot = $ballot;
        $this->user = $user;
        $this->optionKey = $optionKey;
        $this->createdAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBallot(): Ballot
    {
        return $this->ballot;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getOptionKey(): string
    {
        return $this->optionKey;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
