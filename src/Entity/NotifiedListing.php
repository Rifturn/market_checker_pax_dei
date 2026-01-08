<?php

namespace App\Entity;

use App\Repository\NotifiedListingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotifiedListingRepository::class)]
#[ORM\Table(name: 'notified_listing')]
class NotifiedListing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $listingId = null;

    #[ORM\Column(length: 255)]
    private ?string $itemExternalId = null;

    #[ORM\Column(length: 255)]
    private ?string $zone = null;

    #[ORM\Column]
    private ?int $price = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $notifiedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getListingId(): ?string
    {
        return $this->listingId;
    }

    public function setListingId(string $listingId): static
    {
        $this->listingId = $listingId;

        return $this;
    }

    public function getItemExternalId(): ?string
    {
        return $this->itemExternalId;
    }

    public function setItemExternalId(string $itemExternalId): static
    {
        $this->itemExternalId = $itemExternalId;

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

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getNotifiedAt(): ?\DateTimeImmutable
    {
        return $this->notifiedAt;
    }

    public function setNotifiedAt(\DateTimeImmutable $notifiedAt): static
    {
        $this->notifiedAt = $notifiedAt;

        return $this;
    }
}
