<?php

namespace App\Entity;

use App\Repository\EquipmentSetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EquipmentSetRepository::class)]
#[ORM\Table(name: 'equipment_set')]
class EquipmentSet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    // Équipements (wearable uniquement)
    #[ORM\ManyToOne(targetEntity: ItemEntity::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?ItemEntity $helmet = null;

    #[ORM\ManyToOne(targetEntity: ItemEntity::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?ItemEntity $gloves = null;

    #[ORM\ManyToOne(targetEntity: ItemEntity::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?ItemEntity $bracers = null;

    #[ORM\ManyToOne(targetEntity: ItemEntity::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?ItemEntity $chest = null;

    #[ORM\ManyToOne(targetEntity: ItemEntity::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?ItemEntity $legs = null;

    #[ORM\ManyToOne(targetEntity: ItemEntity::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?ItemEntity $boots = null;

    // Armes (wieldable uniquement)
    #[ORM\ManyToOne(targetEntity: ItemEntity::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?ItemEntity $mainHand = null;

    #[ORM\ManyToOne(targetEntity: ItemEntity::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?ItemEntity $offHand = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getHelmet(): ?ItemEntity
    {
        return $this->helmet;
    }

    public function setHelmet(?ItemEntity $helmet): static
    {
        $this->helmet = $helmet;
        return $this;
    }

    public function getGloves(): ?ItemEntity
    {
        return $this->gloves;
    }

    public function setGloves(?ItemEntity $gloves): static
    {
        $this->gloves = $gloves;
        return $this;
    }

    public function getBracers(): ?ItemEntity
    {
        return $this->bracers;
    }

    public function setBracers(?ItemEntity $bracers): static
    {
        $this->bracers = $bracers;
        return $this;
    }

    public function getChest(): ?ItemEntity
    {
        return $this->chest;
    }

    public function setChest(?ItemEntity $chest): static
    {
        $this->chest = $chest;
        return $this;
    }

    public function getLegs(): ?ItemEntity
    {
        return $this->legs;
    }

    public function setLegs(?ItemEntity $legs): static
    {
        $this->legs = $legs;
        return $this;
    }

    public function getBoots(): ?ItemEntity
    {
        return $this->boots;
    }

    public function setBoots(?ItemEntity $boots): static
    {
        $this->boots = $boots;
        return $this;
    }

    public function getMainHand(): ?ItemEntity
    {
        return $this->mainHand;
    }

    public function setMainHand(?ItemEntity $mainHand): static
    {
        $this->mainHand = $mainHand;
        return $this;
    }

    public function getOffHand(): ?ItemEntity
    {
        return $this->offHand;
    }

    public function setOffHand(?ItemEntity $offHand): static
    {
        $this->offHand = $offHand;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
