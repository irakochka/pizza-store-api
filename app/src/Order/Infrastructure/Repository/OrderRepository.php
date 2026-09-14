<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Repository;

use App\Order\Domain\Entity\Order;
use App\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
final class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * @return Order[]
     */
    public function findByUser(User $user, int $page, int $limit): array
    {
        return $this->findBy(
            ['user' => $user],
            ['id' => 'DESC'],
            $limit,
            ($page - 1) * $limit,
        );
    }

    public function findOneByIdAndUser(int $id, User $user): ?Order
    {
        return $this->findOneBy([
            'id' => $id,
            'user' => $user,
        ]);
    }
}
