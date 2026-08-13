<?php

declare(strict_types=1);

namespace App\Product\Presentation\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateProductRequest
{
    public function __construct(
        #[Assert\NotBlank(allowNull: true, normalizer: 'trim')]
        #[Assert\Length(max: 255)]
        public ?string $name = null,

        #[Assert\NotBlank(allowNull: true, normalizer: 'trim')]
        #[Assert\Length(max: 2_000)]
        public ?string $description = null,

        #[Assert\Type('integer')]
        #[Assert\Range(min: 1, max: 100_000)]
        public mixed $price = null,

        #[Assert\Type('integer')]
        #[Assert\Range(min: 1, max: 10_000)]
        public mixed $weight = null,

        #[Assert\NotBlank(allowNull: true, normalizer: 'trim')]
        #[Assert\Length(max: 100)]
        public ?string $category = null,
    ) {
    }

    public function normalizedName(): ?string
    {
        return $this->name === null ? null : trim($this->name);
    }

    public function normalizedDescription(): ?string
    {
        return $this->description === null ? null : trim($this->description);
    }

    public function normalizedCategory(): ?string
    {
        return $this->category === null ? null : trim($this->category);
    }

    public function price(): ?int
    {
        assert($this->price === null || is_int($this->price));

        return $this->price;
    }

    public function weight(): ?int
    {
        assert($this->weight === null || is_int($this->weight));

        return $this->weight;
    }
}
