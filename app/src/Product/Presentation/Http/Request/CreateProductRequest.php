<?php

declare(strict_types=1);

namespace App\Product\Presentation\Http\Request;

use App\Product\Domain\Enum\ProductCategory;

use function assert;
use function is_int;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateProductRequest
{
    public function __construct(
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(max: 255)]
        public string $name,

        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(max: 2_000)]
        public string $description,

        #[Assert\Type('integer')]
        #[Assert\Range(min: 1, max: 100_000)]
        public mixed $price,

        #[Assert\Type('integer')]
        #[Assert\Range(min: 1, max: 10_000)]
        public mixed $weight,

        public ProductCategory $category,
    ) {
    }

    public function normalizedName(): string
    {
        return trim($this->name);
    }

    public function normalizedDescription(): string
    {
        return trim($this->description);
    }

    public function normalizedCategory(): ProductCategory
    {
        return $this->category;
    }

    public function price(): int
    {
        assert(is_int($this->price));

        return $this->price;
    }

    public function weight(): int
    {
        assert(is_int($this->weight));

        return $this->weight;
    }
}
