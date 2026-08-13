<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PaginationRequest
{
    public function __construct(
        #[Assert\Range(min: 1)]
        public int $page = 1,

        #[Assert\Range(min: 1, max: 100)]
        public int $limit = 10,
    ) {
    }
}
