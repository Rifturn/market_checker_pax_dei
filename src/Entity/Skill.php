<?php

namespace App\Entity;

use App\Repository\SkillRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SkillRepository::class)]
class Skill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $externalId = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    private ?string $uiGroup = null;

    #[ORM\Column]
    private ?int $skillLevelCap = null;

    #[ORM\Column]
    private ?int $skillBaseXp = null;

    #[ORM\Column(length: 100)]
    private ?string $entityType = null;

    #[ORM\Column(length: 100)]
    private ?string $entityTypeName = null;

    #[ORM\Column(length: 255)]
    private ?string $listingPath = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $categoryIds = [];

    #[ORM\OneToMany(targetEntity: AvatarSkill::class, mappedBy: 'skill')]
    private Collection $avatarSkills;

    public function __construct()
    {
        $this->avatarSkills = new ArrayCollection();
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

    public function getUiGroup(): ?string
    {
        return $this->uiGroup;
    }

    public function setUiGroup(string $uiGroup): static
    {
        $this->uiGroup = $uiGroup;

        return $this;
    }

    public function getSkillLevelCap(): ?int
    {
        return $this->skillLevelCap;
    }

    public function setSkillLevelCap(int $skillLevelCap): static
    {
        $this->skillLevelCap = $skillLevelCap;

        return $this;
    }

    public function getSkillBaseXp(): ?int
    {
        return $this->skillBaseXp;
    }

    public function setSkillBaseXp(int $skillBaseXp): static
    {
        $this->skillBaseXp = $skillBaseXp;

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
     * @return Collection<int, AvatarSkill>
     */
    public function getAvatarSkills(): Collection
    {
        return $this->avatarSkills;
    }

    public function addAvatarSkill(AvatarSkill $avatarSkill): static
    {
        if (!$this->avatarSkills->contains($avatarSkill)) {
            $this->avatarSkills->add($avatarSkill);
            $avatarSkill->setSkill($this);
        }

        return $this;
    }

    public function removeAvatarSkill(AvatarSkill $avatarSkill): static
    {
        if ($this->avatarSkills->removeElement($avatarSkill)) {
            if ($avatarSkill->getSkill() === $this) {
                $avatarSkill->setSkill(null);
            }
        }

        return $this;
    }
}
