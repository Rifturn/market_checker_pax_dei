<?php

namespace App\Entity;

use App\Repository\AvatarTeleportRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvatarTeleportRepository::class)]
#[ORM\Table(name: 'avatar_teleport')]
#[ORM\Index(columns: ['avatar_id'], name: 'idx_avatar_teleport')]
#[ORM\Index(columns: ['map', 'zone'], name: 'idx_map_zone')]
#[ORM\UniqueConstraint(name: 'unique_avatar_location', columns: ['avatar_id', 'map', 'zone'])]
class AvatarTeleport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Avatar::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Avatar $avatar = null;

    #[ORM\Column(length: 50)]
    private ?string $map = null;

    #[ORM\Column(length: 100)]
    private ?string $zone = null;

    #[ORM\Column]
    private ?bool $unlocked = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->unlocked = false;
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

    public function getMap(): ?string
    {
        return $this->map;
    }

    public function setMap(string $map): static
    {
        $this->map = $map;
        return $this;
    }

    public function getZone(): ?string
    {
        return $this->zone;
    }

    public function setZone(string $zone): static
    {
        $this->zone = $zone;
        return $this;
    }

    public function isUnlocked(): ?bool
    {
        return $this->unlocked;
    }

    public function setUnlocked(bool $unlocked): static
    {
        $this->unlocked = $unlocked;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
