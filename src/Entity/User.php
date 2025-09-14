<?php declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected null|int $id = null;

    #[ORM\Column(length: 64, nullable: true)]
    private null|string $name = null;

    #[ORM\Column(length: 180)]
    private null|string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private null|string $password = null;

    #[ORM\Column]
    private null|DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private null|DateTimeInterface $lastLogin = null;

    #[ORM\ManyToMany(targetEntity: Event::class, mappedBy: 'rsvp')]
    private Collection $rsvp;

    #[ORM\Column(length: 2)]
    private string $locale = 'en';

    #[ORM\Column(enumType: UserStatus::class)]
    private null|UserStatus $status = null;

    #[ORM\Column]
    private bool $public = true;

    #[ORM\Column(length: 40, nullable: true)]
    private null|string $regcode = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private null|Image $image = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private null|string $bio = null;

    #[ORM\Column]
    private bool $verified = false;

    #[ORM\Column]
    private null|bool $restricted = false;

    #[ORM\Column]
    private null|bool $osmConsent = false;

    #[ORM\Column]
    private null|bool $tagging = true;

    #[ORM\OneToMany(targetEntity: Activity::class, mappedBy: 'user')]
    private Collection $activities;

    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'followers')]
    private Collection $following;

    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'following')]
    private Collection $followers;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'sender', orphanRemoval: true)]
    private Collection $messagesSend;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'receiver', orphanRemoval: true)]
    private Collection $messagesReceived;

    #[ORM\Column]
    private null|bool $notification = null;

    #[ORM\Column(nullable: true)]
    private null|array $notificationSettings = null;

    public function __construct()
    {
        $this->rsvp = new ArrayCollection();
        $this->activities = new ArrayCollection();
        $this->followers = new ArrayCollection();
        $this->following = new ArrayCollection();
        $this->messagesSend = new ArrayCollection();
        $this->messagesReceived = new ArrayCollection();
    }

    public function getId(): null|int
    {
        return $this->id;
    }

    public function getEmail(): null|string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    #[\Override]
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    #[\Override]
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    #[\Override]
    public function getPassword(): null|string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    #[\Override]
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getCreatedAt(): null|DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getName(): null|string
    {
        return $this->name;
    }

    public function setName(null|string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getRsvpEvents(): Collection
    {
        return $this->rsvp;
    }

    public function addRsvpEvent(Event $event): static
    {
        if (!$this->rsvp->contains($event)) {
            $this->rsvp->add($event);
            $event->addRsvp($this);
        }

        return $this;
    }

    public function removeRsvpEvent(Event $event): static
    {
        if ($this->rsvp->removeElement($event)) {
            $event->removeRsvp($this);
        }

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    public function setStatus(UserStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): static
    {
        $this->public = $public;

        return $this;
    }

    public function getRegcode(): null|string
    {
        return $this->regcode;
    }

    public function setRegcode(null|string $regcode): static
    {
        $this->regcode = $regcode;

        return $this;
    }

    public function getImage(): null|Image
    {
        return $this->image;
    }

    public function setImage(null|Image $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getBio(): null|string
    {
        return $this->bio;
    }

    public function setBio(null|string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): static
    {
        $this->verified = $verified;

        return $this;
    }

    /**
     * @return Collection<int, Activity>
     */
    public function getActivities(): Collection
    {
        return $this->activities;
    }

    public function addActivity(Activity $activity): static
    {
        if (!$this->activities->contains($activity)) {
            $this->activities->add($activity);
            $activity->setUser($this);
        }

        return $this;
    }

    public function removeActivity(Activity $activity): static
    {
        // set the owning side to null (unless already changed)
        if ($this->activities->removeElement($activity) && $activity->getUser() === $this) {
            $activity->setUser(null);
        }

        return $this;
    }

    public function getFollowers(): Collection
    {
        return $this->followers;
    }

    public function addFollower(User $user): void
    {
        if ($this->getId() === $user->getId()) {
            return;
        }
        if ($this->followers->contains($user)) {
            return;
        }
        $this->followers->add($user);
    }

    public function removeFollower(User $user): void
    {
        if ($this->followers->contains($user)) {
            $this->followers->removeElement($user);
        }
    }

    public function getFollowing(): Collection
    {
        return $this->following;
    }

    public function addFollowing(User $user): void
    {
        if ($this->getId() === $user->getId()) {
            return;
        }
        if ($this->following->contains($user)) {
            return;
        }
        $this->following->add($user);
    }

    public function removeFollowing(User $user): void
    {
        if ($this->following->contains($user)) {
            $this->following->removeElement($user);
        }
    }

    public function isRestricted(): null|bool
    {
        return $this->restricted;
    }

    public function setRestricted(bool $restricted): static
    {
        $this->restricted = $restricted;

        return $this;
    }

    public function isOsmConsent(): null|bool
    {
        return $this->osmConsent;
    }

    public function setOsmConsent(bool $osmConsent): static
    {
        $this->osmConsent = $osmConsent;

        return $this;
    }

    public function getLastLogin(): null|DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(DateTimeInterface $lastLogin): static
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessagesSend(): Collection
    {
        return $this->messagesSend;
    }

    public function addMessagesSend(Message $messagesSend): static
    {
        if (!$this->messagesSend->contains($messagesSend)) {
            $this->messagesSend->add($messagesSend);
            $messagesSend->setSender($this);
        }

        return $this;
    }

    public function removeMessagesSend(Message $messagesSend): static
    {
        // set the owning side to null (unless already changed)
        if ($this->messagesSend->removeElement($messagesSend) && $messagesSend->getSender() === $this) {
            $messagesSend->setSender(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessagesReceived(): Collection
    {
        return $this->messagesReceived;
    }

    public function addMessagesReceived(Message $messagesReceived): static
    {
        if (!$this->messagesReceived->contains($messagesReceived)) {
            $this->messagesReceived->add($messagesReceived);
            $messagesReceived->setReceiver($this);
        }

        return $this;
    }

    public function removeMessagesReceived(Message $messagesReceived): static
    {
        // set the owning side to null (unless already changed)
        if ($this->messagesReceived->removeElement($messagesReceived) && $messagesReceived->getReceiver() === $this) {
            $messagesReceived->setReceiver(null);
        }

        return $this;
    }

    public function isTagging(): null|bool
    {
        return $this->tagging;
    }

    public function setTagging(bool $tagging): static
    {
        $this->tagging = $tagging;

        return $this;
    }

    public function isNotification(): null|bool
    {
        return $this->notification;
    }

    public function setNotification(bool $notification): static
    {
        $this->notification = $notification;

        return $this;
    }

    public function getNotificationSettings(): NotificationSettings
    {
        return NotificationSettings::fromJson($this->notificationSettings);
    }

    public function setNotificationSettings(null|NotificationSettings $notificationSettings): static
    {
        $this->notificationSettings = $notificationSettings->jsonSerialize();

        return $this;
    }
}
