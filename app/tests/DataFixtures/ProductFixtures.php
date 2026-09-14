<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\Enum\ProductCategory;
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
            ProductCategory::Pizza,
        ));

        $manager->persist(new Product(
            'Пепперони',
            'Пицца с пепперони, томатным соусом и сыром',
            650,
            470,
            ProductCategory::Pizza,
        ));

        $manager->persist(new Product(
            'Кола',
            'Газированный напиток',
            150,
            500,
            ProductCategory::Drink,
        ));

        $manager->flush();
    }
}
