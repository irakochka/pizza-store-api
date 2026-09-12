<?php

declare(strict_types=1);

namespace App\Order\Presentation\Http\Request;

use App\Order\Domain\Enum\DeliveryType;
use App\Order\Domain\ValueObject\DeliveryAddress;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class CreateOrderRequest
{
    /**
     * @param array<string, mixed>|null $deliveryAddress
     */
    public function __construct(
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Choice(choices: ['pickup', 'courier'])]
        public string $deliveryType,

        #[Assert\Type('array')]
        public ?array $deliveryAddress = null,
    ) {
    }

    public function deliveryType(): DeliveryType
    {
        return DeliveryType::from($this->deliveryType);
    }

    public function deliveryAddress(): ?DeliveryAddress
    {
        if ($this->deliveryType !== DeliveryType::Courier->value || $this->deliveryAddress === null) {
            return null;
        }

        return new DeliveryAddress(
            trim($this->deliveryAddress['region']),
            trim($this->deliveryAddress['city']),
            trim($this->deliveryAddress['street']),
            trim($this->deliveryAddress['house']),
            trim($this->deliveryAddress['entrance']),
            trim($this->deliveryAddress['apartment']),
            trim($this->deliveryAddress['postalCode']),
        );
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if ($this->deliveryType !== DeliveryType::Courier->value) {
            return;
        }

        if ($this->deliveryAddress === null) {
            $context->buildViolation('Delivery address is required for courier delivery.')
                ->atPath('deliveryAddress')
                ->addViolation();

            return;
        }

        foreach ($this->requiredAddressFields() as $field) {
            if (!array_key_exists($field, $this->deliveryAddress)) {
                $context->buildViolation('This field is required.')
                    ->atPath('deliveryAddress.' . $field)
                    ->addViolation();

                continue;
            }

            if (!is_string($this->deliveryAddress[$field]) || trim($this->deliveryAddress[$field]) === '') {
                $context->buildViolation('This value should be a non-empty string.')
                    ->atPath('deliveryAddress.' . $field)
                    ->addViolation();
            }
        }
    }

    /**
     * @return list<string>
     */
    private function requiredAddressFields(): array
    {
        return [
            'region',
            'city',
            'street',
            'house',
            'entrance',
            'apartment',
            'postalCode',
        ];
    }
}
