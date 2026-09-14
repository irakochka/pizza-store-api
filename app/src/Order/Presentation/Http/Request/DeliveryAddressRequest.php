<?php

declare(strict_types=1);

namespace App\Order\Presentation\Http\Request;

use App\Order\Domain\ValueObject\DeliveryAddress;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class DeliveryAddressRequest
{
    public function __construct(
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(max: 255)]
        public string $region,

        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(max: 255)]
        public string $city,

        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(max: 255)]
        public string $street,

        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(max: 50)]
        public string $house,

        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(max: 50)]
        public string $entrance,

        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(max: 50)]
        public string $apartment,

        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Regex('/^\d{6}$/')]
        public string $postalCode,
    ) {
    }

    public function toValueObject(): DeliveryAddress
    {
        return new DeliveryAddress(
            trim($this->region),
            trim($this->city),
            trim($this->street),
            trim($this->house),
            trim($this->entrance),
            trim($this->apartment),
            trim($this->postalCode),
        );
    }
}
