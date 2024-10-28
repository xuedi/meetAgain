<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

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
    private ?\DateTimeInterface $start = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $stop = null;

    #[ORM\Column(nullable: true)]
    private ?int $recurringOf = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $recurringRule = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $User = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Location $Location = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $CreatedAt = null;

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

    public function getStart(): ?\DateTimeInterface
    {
        return $this->start;
    }

    public function setStart(\DateTimeInterface $start): static
    {
        $this->start = $start;

        return $this;
    }

    public function getStop(): ?\DateTimeInterface
    {
        return $this->stop;
    }

    public function setStop(?\DateTimeInterface $stop): static
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

    public function getRecurringRule(): ?string
    {
        return $this->recurringRule;
    }

    public function setRecurringRule(?string $recurringRule): static
    {
        $this->recurringRule = $recurringRule;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->User;
    }

    public function setUser(?User $user): static
    {
        $this->User = $user;

        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->Location;
    }

    public function setLocation(?Location $Location): static
    {
        $this->Location = $Location;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->CreatedAt;
    }

    public function setCreatedAt(\DateTimeImmutable $CreatedAt): static
    {
        $this->CreatedAt = $CreatedAt;

        return $this;
    }
}
