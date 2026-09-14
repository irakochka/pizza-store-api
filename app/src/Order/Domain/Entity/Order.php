<?php

declare(strict_types=1);

namespace App\Order\Domain\Entity;

use App\Cart\Domain\Entity\Cart;
use App\Order\Domain\Enum\DeliveryType;
use App\Order\Domain\Enum\OrderStatus;
use App\Order\Domain\Exception\InvalidOrderStatusTransitionException;
use App\Order\Domain\ValueObject\DeliveryAddress;
use App\Order\Infrastructure\Repository\OrderRepository;
use App\User\Domain\Entity\User;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use LogicException;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
class Order
{
    private const ALLOWED_TRANSITIONS = [
        'created' => ['paid', 'cancelled'],
        'paid' => ['in_progress', 'cancelled'],
        'in_progress' => ['delivering', 'cancelled'],
        'delivering' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /**
     * @var int|null doctrine assigns this value after persistence
     */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 30, enumType: DeliveryType::class)]
    private DeliveryType $deliveryType;

    /**
     * @var array{
     *     region: string,
     *     city: string,
     *     street: string,
     *     house: string,
     *     entrance: string,
     *     apartment: string,
     *     postalCode: string
     * }|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $deliveryAddress;

    #[ORM\Column(length: 30, enumType: OrderStatus::class)]
    private OrderStatus $status;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(
        targetEntity: OrderItem::class,
        mappedBy: 'order',
        cascade: ['persist'],
        orphanRemoval: true
    )]
    private Collection $items;

    #[ORM\Column(type: Types::INTEGER)]
    private int $totalPrice;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    public function __construct(
        User $user,
        DeliveryType $deliveryType,
        ?DeliveryAddress $deliveryAddress,
    ) {
        $this->user = $user;
        $this->deliveryType = $deliveryType;
        $this->deliveryAddress = $deliveryAddress?->toArray();
        $this->status = OrderStatus::Created;
        $this->items = new ArrayCollection();
        $this->totalPrice = 0;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getDeliveryType(): DeliveryType
    {
        return $this->deliveryType;
    }

    /**
     * @return array{
     *     region: string,
     *     city: string,
     *     street: string,
     *     house: string,
     *     entrance: string,
     *     apartment: string,
     *     postalCode: string
     * }|null
     */
    public function getDeliveryAddress(): ?array
    {
        return $this->deliveryAddress;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function getTotalPrice(): int
    {
        return $this->totalPrice;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    private function addItem(int $productId, string $productName, int $unitPrice, int $quantity): void
    {
        $item = new OrderItem($this, $productId, $productName, $unitPrice, $quantity);

        $this->items->add($item);
        $this->totalPrice += $item->getTotalPrice();
    }

    public static function fromCart(Cart $cart, DeliveryType $deliveryType, ?DeliveryAddress $deliveryAddress): self
    {
        $cart->assertCanCheckout();

        $order = new self($cart->getUser(), $deliveryType, $deliveryAddress);

        foreach ($cart->getItems() as $cartItem) {
            $product = $cartItem->getProduct();
            $productId = $product->getId();

            if ($productId === null) {
                throw new LogicException('Persisted product must have an id.');
            }

            $order->addItem(
                $productId,
                $product->getName(),
                $product->getPrice(),
                $cartItem->getQuantity(),
            );
        }

        return $order;
    }

    public function transitionTo(OrderStatus $nextStatus): void
    {
        if (!in_array($nextStatus->value, self::ALLOWED_TRANSITIONS[$this->status->value], true)) {
            throw new InvalidOrderStatusTransitionException(sprintf('Cannot change order status from %s to %s.', $this->status->value, $nextStatus->value));
        }

        $this->status = $nextStatus;
    }
}
