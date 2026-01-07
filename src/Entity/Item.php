<?php

namespace App\Entity;

class Item
{
    private string $id;
    private array $name; // ['De' => ..., 'En' => ..., 'Es' => ..., 'Fr' => ..., 'Pl' => ...]
    private string $iconPath;
    private string $url;
    private string $category;

    public function __construct(string $id, array $name, string $iconPath, string $url, string $category = '')
    {
        $this->id = $id;
        $this->name = $name;
        $this->iconPath = $iconPath;
        $this->url = $url;
        $this->category = $category;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): array
    {
        return $this->name;
    }

    public function getIconPath(): string
    {
        return $this->iconPath;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getCategory(): string
    {
        return $this->category;
    }
}
