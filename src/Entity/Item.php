<?php

namespace App\Entity;

class Item
{
    private string $id;
    private array $name; // ['De' => ..., 'En' => ..., 'Es' => ..., 'Fr' => ..., 'Pl' => ...]
    private string $iconPath;
    private string $url;
    private string $category;
    private string $type;
    private string $urlApi;

    public function __construct(string $id, array $name, string $iconPath, string $url, string $category = '', string $type = '', string $urlApi = '')
    {
        $this->id = $id;
        $this->name = $name;
        $this->iconPath = $iconPath;
        $this->url = $url;
        $this->category = $category;
        $this->type = $type;
        $this->urlApi = $urlApi;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getUrlApi(): string
    {
        return $this->urlApi;
    }

    public function setUrlApi(string $urlApi): self
    {
        $this->urlApi = $urlApi;
        return $this;
    }
}
