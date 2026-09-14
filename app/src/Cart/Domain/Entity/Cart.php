<?php

declare(strict_types=1);

namespace App\Cart\Domain\Entity;

use App\Cart\Domain\Exception\CartLimitExceededException;
use App\Cart\Domain\Exception\EmptyCartException;
use App\Cart\Infrastructure\Repository\CartRepository;
use App\Product\Domain\Entity\Product;
use App\Product\Domain\Enum\ProductCategory;
use App\User\Domain\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CartRepository::class)]
#[ORM\Table(name: 'carts')]
#[ORM\UniqueConstraint(name: 'uniq_carts_user', fields: ['user'])]
class Cart
{
    private const int MAX_PIZZAS = 10;
    private const int MAX_DRINKS = 20;
    private const int MAX_ORDER_POSITIONS = 20;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /**
     * @var int|null doctrine assigns this value after persistence
     */
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /**
     * @var Collection<int, CartItem>
     */
    #[ORM\OneToMany(
        targetEntity: CartItem::class,
        mappedBy: 'cart',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $items;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return Collection<int, CartItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function setProductQuantity(Product $product, int $quantity): void
    {
        $item = $this->findItem($product);

        if ($quantity <= 0) {
            if ($item !== null) {
                $this->items->removeElement($item);
            }

            return;
        }

        if ($item === null) {
            $item = new CartItem($this, $product, $quantity);
            $this->items->add($item);
        } else {
            $item->changeQuantity($quantity);
        }

        $this->assertWithinLimits();
    }

    public function clear(): void
    {
        $this->items->clear();
    }

    private function findItem(Product $product): ?CartItem
    {
        foreach ($this->items as $item) {
            if ($item->getProduct()->getId() === $product->getId()) {
                return $item;
            }
        }

        return null;
    }

    private function assertWithinLimits(): void
    {
        $pizzas = 0;
        $drinks = 0;

        foreach ($this->items as $item) {
            $category = $item->getProduct()->getCategory();

            if ($category === ProductCategory::Pizza) {
                $pizzas += $item->getQuantity();
            }

            if ($category === ProductCategory::Drink) {
                $drinks += $item->getQuantity();
            }
        }

        if ($pizzas > self::MAX_PIZZAS) {
            throw new CartLimitExceededException('Cart cannot contain more than 10 pizzas.');
        }

        if ($drinks > self::MAX_DRINKS) {
            throw new CartLimitExceededException('Cart cannot contain more than 20 drinks.');
        }
    }

    public function assertCanCheckout(): void
    {
        if ($this->items->isEmpty()) {
            throw new EmptyCartException('Cart must contain at least one product.');
        }

        if ($this->items->count() > self::MAX_ORDER_POSITIONS) {
            throw new CartLimitExceededException('Order cannot contain more than 20 positions.');
        }

        $this->assertWithinLimits();
    }
}
