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
    protected ?int $id = null;

    #[ORM\Column]
    private ?bool $initial = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private DateTimeInterface $start;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $stop = null;

    #[ORM\Column(nullable: true)]
    private ?int $recurringOf = null;

    #[ORM\Column(type: "integer", nullable: true, enumType: EventIntervals::class)]
    private ?EventIntervals $recurringRule = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Location $location = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToMany(targetEntity: Host::class)]
    private Collection $host;

    #[JoinTable(name: 'event_rsvp')]
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'rsvp')]
    private Collection $rsvp;

    #[ORM\ManyToOne]
    private ?Image $previewImage = null;

    #[ORM\Column(nullable: true, enumType: EventTypes::class)]
    private ?EventTypes $type = null;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'event')]
    private Collection $comments;

    /**
     * @var Collection<int, EventTranslation>
     */
    #[ORM\OneToMany(targetEntity: EventTranslation::class, mappedBy: 'event')]
    private Collection $translations;

    /**
     * @var Collection<int, Image>
     */
    #[ORM\OneToMany(targetEntity: Image::class, mappedBy: 'event')]
    private Collection $images;

    #[ORM\Column]
    private ?bool $published = null;

    #[ORM\Column]
    private ?bool $featured = null;

    public function __construct()
    {
        $this->host = new ArrayCollection();
        $this->rsvp = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->translations = new ArrayCollection();
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

    public function getHost(): Collection
    {
        return $this->host;
    }

    public function setHost(Collection $hosts): static
    {
        $this->host = $hosts;

        return $this;
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

    public function toggleRsvp(User $user): bool
    {
        if (!$this->rsvp->contains($user)) {
            $this->rsvp->add($user);

            return true;
        }
        $this->rsvp->removeElement($user);

        return false;
    }

    public function addRsvp(User $user): static
    {
        if (!$this->rsvp->contains($user)) {
            $this->rsvp->add($user);
        }

        return $this;
    }

    public function hasRsvp(User $user): bool
    {
        return $this->rsvp->contains($user);
    }

    public function removeRsvp(User $user): static
    {
        $this->rsvp->removeElement($user);

        return $this;
    }

    public function getPreviewImage(): ?Image
    {
        return $this->previewImage;
    }

    public function setPreviewImage(?Image $previewImage): static
    {
        $this->previewImage = $previewImage;

        return $this;
    }

    public function getType(): ?EventTypes
    {
        return $this->type;
    }

    public function setType(?EventTypes $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function hasMap(): bool
    {
        if (!$this->getLocation() instanceof \App\Entity\Location) {
            return false;
        }
        return $this->getLocation()->getLatitude() !== null && $this->getLocation()->getLongitude() !== null;
    }

    public function getTitle(string $language): string
    {
        return $this->findTranslation($language)?->getTitle() ?? '';
    }

    public function getTeaser(string $language): string
    {
        return $this->findTranslation($language)?->getTeaser() ?? '';
    }

    public function getDescription(string $language): string
    {
        return $this->findTranslation($language)?->getDescription() ?? '';
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setEvent($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        // set the owning side to null (unless already changed)
        if ($this->comments->removeElement($comment) && $comment->getEvent() === $this) {
            $comment->setEvent(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, EventTranslation>
     */
    public function getTranslation(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(EventTranslation $translation): static
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setEvent($this);
        }

        return $this;
    }

    public function removeTranslation(EventTranslation $translation): static
    {
        if ($this->translations->removeElement($translation) && $translation->getEvent() === $this) {
            $translation->setEvent(null);
        }

        return $this;
    }

    public function findTranslation(string $language): ?EventTranslation
    {
        foreach ($this->translations as $translation) {
            if ($translation->getLanguage() === $language) {
                return $translation;
            }
        }
        return null;
    }

    public function isPublished(): ?bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): static
    {
        $this->published = $published;

        return $this;
    }

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setEvent($this);
        }

        return $this;
    }

    public function removeImage(Image $image): static
    {
        // set the owning side to null (unless already changed)
        if ($this->images->removeElement($image) && $image->getEvent() === $this) {
            $image->setEvent(null);
        }

        return $this;
    }

    public function isFeatured(): ?bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): static
    {
        $this->featured = $featured;

        return $this;
    }
}
