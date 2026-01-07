<?php

namespace App\Entity;

class Listing
{
    private string $id;
    private string $itemId;
    private int $quantity;
    private int $price;
    private string $zone;
    private int $lastSeen;

    public function __construct(
        string $id,
        string $itemId,
        int $quantity,
        int $price,
        string $zone,
        int $lastSeen
    ) {
        $this->id = $id;
        $this->itemId = $itemId;
        $this->quantity = $quantity;
        $this->price = $price;
        $this->zone = $zone;
        $this->lastSeen = $lastSeen;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getItemId(): string
    {
        return $this->itemId;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function getZone(): string
    {
        return $this->zone;
    }

    public function getLastSeen(): int
    {
        return $this->lastSeen;
    }
}
