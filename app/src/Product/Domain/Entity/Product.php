<?php

declare(strict_types=1);

namespace App\Product\Domain\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Product\Infrastructure\Repository\ProductRepository;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'products')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: Types::TEXT)]
    private string $description;

    #[ORM\Column(type: Types::INTEGER)]
    private int $price;

    #[ORM\Column(type: Types::INTEGER)]
    private int $weight;

    #[ORM\Column(length: 100)]
    private string $category;

    public function __construct(string $name, string $description, int $price, int $weight, string $category)
    {
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->weight = $weight;
        $this->category = $category;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function updateDetails(string $name, string $description, int $price, int $weight, string $category): void {
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->weight = $weight;
        $this->category = $category;
    }
}
