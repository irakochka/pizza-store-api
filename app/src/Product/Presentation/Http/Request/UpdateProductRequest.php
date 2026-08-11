<?php

declare(strict_types=1);

namespace App\Product\Presentation\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateProductRequest
{
    public function __construct(
        #[Assert\Length(min: 1, max: 255)]
        public ?string $name = null,

        #[Assert\Length(min: 1, max: 2000)]
        public ?string $description = null,

        #[Assert\Positive]
        public ?int $price = null,

        #[Assert\Positive]
        public ?int $weight = null,

        #[Assert\Length(min: 1, max: 100)]
        public ?string $category = null,
    ) {
    }
}
