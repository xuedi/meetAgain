<?php declare(strict_types=1);

namespace App\Entity;

use App\Repository\EventRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinTable;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $initial = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private DateTimeInterface $start;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $stop = null;

    #[ORM\Column(nullable: true)]
    private ?int $recurringOf = null;

    //#[ORM\Column(type: "string", nullable: true, enumType: Color::class)]
    #[ORM\Column(enumType: EventIntervals::class)]
    private ?EventIntervals $recurringRule;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Location $location = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 128)]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: Host::class)]
    private Collection $host;

    #[JoinTable(name: 'event_rsvp')]
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'rsvp')]
    private Collection $rsvp;

    public function __construct()
    {
        $this->host = new ArrayCollection();
        $this->rsvp = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isInitial(): ?bool
    {
        return $this->initial;
    }

    public function setInitial(bool $initial): static
    {
        $this->initial = $initial;

        return $this;
    }

    public function getStart(): DateTimeInterface
    {
        return $this->start;
    }

    public function setStart(DateTimeInterface $start): static
    {
        $this->start = $start;

        return $this;
    }

    public function getStop(): ?DateTimeInterface
    {
        return $this->stop;
    }

    public function setStop(?DateTimeInterface $stop): static
    {
        $this->stop = $stop;

        return $this;
    }

    public function getRecurringOf(): ?int
    {
        return $this->recurringOf;
    }

    public function setRecurringOf(?int $recurringOf): static
    {
        $this->recurringOf = $recurringOf;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getRecurringRule(): ?EventIntervals
    {
        return $this->recurringRule;
    }

    public function setRecurringRule(?EventIntervals $recurringRule): static
    {
        $this->recurringRule = $recurringRule;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getHost(): Collection
    {
        return $this->host;
    }

    public function addHost(Host $host): static
    {
        if (!$this->host->contains($host)) {
            $this->host->add($host);
        }

        return $this;
    }

    public function removeHost(Host $host): static
    {
        $this->host->removeElement($host);

        return $this;
    }

    public function getRsvp(): Collection
    {
        return $this->rsvp;
    }

    public function addRsvp(User $user): static
    {
        if (!$this->rsvp->contains($user)) {
            $this->rsvp->add($user);
        }

        return $this;
    }

    public function removeRsvp(User $user): static
    {
        $this->rsvp->removeElement($user);

        return $this;
    }
}
