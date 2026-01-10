<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Index(columns: ['created_at'], name: 'idx_notification_created')]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Avatar::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Avatar $avatar = null;

    #[ORM\Column(length: 20)]
    private ?string $type = null; // 'skill_up' ou 'teleport_unlocked'

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(targetEntity: NotificationReaction::class, mappedBy: 'notification', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $reactions;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->reactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAvatar(): ?Avatar
    {
        return $this->avatar;
    }

    public function setAvatar(?Avatar $avatar): static
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, NotificationReaction>
     */
    public function getReactions(): Collection
    {
        return $this->reactions;
    }

    public function addReaction(NotificationReaction $reaction): static
    {
        if (!$this->reactions->contains($reaction)) {
            $this->reactions->add($reaction);
            $reaction->setNotification($this);
        }

        return $this;
    }

    public function removeReaction(NotificationReaction $reaction): static
    {
        if ($this->reactions->removeElement($reaction)) {
            if ($reaction->getNotification() === $this) {
                $reaction->setNotification(null);
            }
        }

        return $this;
    }

    /**
     * Get reactions grouped by emoji
     */
    public function getReactionCounts(): array
    {
        $counts = [];
        foreach ($this->reactions as $reaction) {
            $emoji = $reaction->getEmoji();
            if (!isset($counts[$emoji])) {
                $counts[$emoji] = [
                    'count' => 0,
                    'users' => []
                ];
            }
            $counts[$emoji]['count']++;
            $counts[$emoji]['users'][] = $reaction->getUser();
        }
        return $counts;
    }
}
