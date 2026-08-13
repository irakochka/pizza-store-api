<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\Product\Domain\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $manager->persist(new Product(
            'Маргарита',
            'Классическая пицца с томатами и сыром',
            500,
            450,
            'pizza',
        ));

        $manager->persist(new Product(
            'Пепперони',
            'Пицца с пепперони, томатным соусом и сыром',
            650,
            470,
            'pizza',
        ));

        $manager->persist(new Product(
            'Кола',
            'Газированный напиток',
            150,
            500,
            'drink',
        ));

        $manager->flush();
    }
}
