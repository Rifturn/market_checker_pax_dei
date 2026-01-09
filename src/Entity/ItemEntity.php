<?php

namespace App\Entity;

use App\Repository\ItemEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ItemEntityRepository::class)]
#[ORM\Table(name: 'item')]
class ItemEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $externalId = null;

    #[ORM\Column(length: 50)]
    private ?string $quality = 'common';

    #[ORM\Column(type: Types::JSON)]
    private array $name = [];

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $iconPath = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $url = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Category $category = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $urlApi = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $armorCategory = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $slotCategory = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
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

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): static
    {
        $this->externalId = $externalId;
        return $this;
    }

    public function getQuality(): ?string
    {
        return $this->quality;
    }

    public function setQuality(string $quality): static
    {
        $this->quality = $quality;
        return $this;
    }

    public function getName(): array
    {
        return $this->name;
    }

    public function setName(array $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getIconPath(): ?string
    {
        return $this->iconPath;
    }

    public function setIconPath(?string $iconPath): static
    {
        $this->iconPath = $iconPath;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getUrlApi(): ?string
    {
        return $this->urlApi;
    }

    public function setUrlApi(?string $urlApi): static
    {
        $this->urlApi = $urlApi;
        return $this;
    }

    public function getArmorCategory(): ?string
    {
        return $this->armorCategory;
    }

    public function setArmorCategory(?string $armorCategory): static
    {
        $this->armorCategory = $armorCategory;
        return $this;
    }

    public function getSlotCategory(): ?string
    {
        return $this->slotCategory;
    }

    public function setSlotCategory(?string $slotCategory): static
    {
        $this->slotCategory = $slotCategory;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Retourne le type d'armure pour les wearables: L (léger), M (medium), P (plaque)
     */
    public function getArmorType(): ?string
    {
        if (!$this->armorCategory) {
            return null;
        }

        if (str_contains($this->armorCategory, 'Cloth')) {
            return 'L'; // Léger
        }
        if (str_contains($this->armorCategory, 'Leather')) {
            return 'M'; // Medium
        }
        if (str_contains($this->armorCategory, 'Plate') || str_contains($this->armorCategory, 'Mail')) {
            return 'P'; // Plaque
        }

        return null;
    }
}
