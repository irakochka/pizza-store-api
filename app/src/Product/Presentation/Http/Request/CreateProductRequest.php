<?php

declare(strict_types=1);

namespace App\Product\Presentation\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateProductRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Length(max: 2000)]
        public string $description,

        #[Assert\Positive]
        public int $price,

        #[Assert\Positive]
        public int $weight,

        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public string $category,
    ) {
    }
}
