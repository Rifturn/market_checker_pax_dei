<?php

namespace App\Entity;

use App\Repository\NotificationReadRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationReadRepository::class)]
#[ORM\UniqueConstraint(name: 'user_notification_unique', columns: ['user_id', 'notification_id'])]
class NotificationRead
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Notification::class, inversedBy: 'reads')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Notification $notification = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $readAt = null;

    public function __construct()
    {
        $this->readAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getNotification(): ?Notification
    {
        return $this->notification;
    }

    public function setNotification(?Notification $notification): static
    {
        $this->notification = $notification;
        return $this;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function setReadAt(\DateTimeImmutable $readAt): static
    {
        $this->readAt = $readAt;
        return $this;
    }
}
