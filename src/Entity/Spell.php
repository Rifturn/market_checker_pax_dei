<?php

namespace App\Entity;

use App\Repository\SpellRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SpellRepository::class)]
class Spell
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $externalId = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $iconPath = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?float $cooldownDuration = null;

    #[ORM\Column(nullable: true)]
    private ?float $range = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $costAttribute = null;

    #[ORM\Column(nullable: true)]
    private ?float $costAmountMin = null;

    #[ORM\Column(length: 100)]
    private ?string $entityType = null;

    #[ORM\Column(length: 100)]
    private ?string $entityTypeName = null;

    #[ORM\Column(length: 255)]
    private ?string $listingPath = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $categoryIds = [];

    #[ORM\OneToMany(targetEntity: SpellItem::class, mappedBy: 'spell')]
    private Collection $spellItems;

    public function __construct()
    {
        $this->spellItems = new ArrayCollection();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCooldownDuration(): ?float
    {
        return $this->cooldownDuration;
    }

    public function setCooldownDuration(?float $cooldownDuration): static
    {
        $this->cooldownDuration = $cooldownDuration;

        return $this;
    }

    public function getRange(): ?float
    {
        return $this->range;
    }

    public function setRange(?float $range): static
    {
        $this->range = $range;

        return $this;
    }

    public function getCostAttribute(): ?string
    {
        return $this->costAttribute;
    }

    public function setCostAttribute(?string $costAttribute): static
    {
        $this->costAttribute = $costAttribute;

        return $this;
    }

    public function getCostAmountMin(): ?float
    {
        return $this->costAmountMin;
    }

    public function setCostAmountMin(?float $costAmountMin): static
    {
        $this->costAmountMin = $costAmountMin;

        return $this;
    }

    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): static
    {
        $this->entityType = $entityType;

        return $this;
    }

    public function getEntityTypeName(): ?string
    {
        return $this->entityTypeName;
    }

    public function setEntityTypeName(string $entityTypeName): static
    {
        $this->entityTypeName = $entityTypeName;

        return $this;
    }

    public function getListingPath(): ?string
    {
        return $this->listingPath;
    }

    public function setListingPath(string $listingPath): static
    {
        $this->listingPath = $listingPath;

        return $this;
    }

    public function getCategoryIds(): ?array
    {
        return $this->categoryIds;
    }

    public function setCategoryIds(?array $categoryIds): static
    {
        $this->categoryIds = $categoryIds;

        return $this;
    }

    /**
     * @return Collection<int, SpellItem>
     */
    public function getSpellItems(): Collection
    {
        return $this->spellItems;
    }

    public function addSpellItem(SpellItem $spellItem): static
    {
        if (!$this->spellItems->contains($spellItem)) {
            $this->spellItems->add($spellItem);
            $spellItem->setSpell($this);
        }

        return $this;
    }

    public function removeSpellItem(SpellItem $spellItem): static
    {
        if ($this->spellItems->removeElement($spellItem)) {
            // set the owning side to null (unless already changed)
            if ($spellItem->getSpell() === $this) {
                $spellItem->setSpell(null);
            }
        }

        return $this;
    }
}
