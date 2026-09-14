<?php

declare(strict_types=1);

namespace App\Order\Domain\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_items')]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Order $order;

    #[ORM\Column(type: Types::INTEGER)]
    private int $productId;

    #[ORM\Column(length: 255)]
    private string $productName;

    #[ORM\Column(type: Types::INTEGER)]
    private int $unitPrice;

    #[ORM\Column(type: Types::INTEGER)]
    private int $quantity;

    public function __construct(Order $order, int $productId, string $productName, int $unitPrice, int $quantity)
    {
        $this->order = $order;
        $this->productId = $productId;
        $this->productName = $productName;
        $this->unitPrice = $unitPrice;
        $this->quantity = $quantity;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getUnitPrice(): int
    {
        return $this->unitPrice;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getTotalPrice(): int
    {
        return $this->unitPrice * $this->quantity;
    }
}
