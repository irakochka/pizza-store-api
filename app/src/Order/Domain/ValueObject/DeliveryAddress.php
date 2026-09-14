<?php

declare(strict_types=1);

namespace App\Order\Domain\ValueObject;

final readonly class DeliveryAddress
{
    public function __construct(
        public string $region,
        public string $city,
        public string $street,
        public string $house,
        public string $entrance,
        public string $apartment,
        public string $postalCode,
    ) {
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
     * }
     */
    public function toArray(): array
    {
        return [
            'region' => $this->region,
            'city' => $this->city,
            'street' => $this->street,
            'house' => $this->house,
            'entrance' => $this->entrance,
            'apartment' => $this->apartment,
            'postalCode' => $this->postalCode,
        ];
    }
}
