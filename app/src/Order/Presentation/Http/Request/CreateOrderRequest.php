<?php

declare(strict_types=1);

namespace App\Order\Presentation\Http\Request;

use App\Order\Domain\Enum\DeliveryType;
use App\Order\Domain\ValueObject\DeliveryAddress;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class CreateOrderRequest
{
    public function __construct(
        public DeliveryType $deliveryType,

        #[Assert\Valid]
        public ?DeliveryAddressRequest $deliveryAddress = null,
    ) {
    }

    public function deliveryType(): DeliveryType
    {
        return $this->deliveryType;
    }

    public function deliveryAddress(): ?DeliveryAddress
    {
        if ($this->deliveryType !== DeliveryType::Courier) {
            return null;
        }

        return $this->deliveryAddress?->toValueObject();
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if ($this->deliveryType !== DeliveryType::Courier) {
            return;
        }

        if ($this->deliveryAddress === null) {
            $context
                ->buildViolation('Delivery address is required for courier delivery.')
                ->atPath('deliveryAddress')
                ->addViolation();
        }
    }
}
