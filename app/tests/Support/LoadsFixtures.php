<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Tests\DataFixtures\ProductFixtures;
use App\Tests\DataFixtures\UserFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

trait LoadsFixtures
{
    protected EntityManagerInterface $entityManager;

    protected function loadFixtures(): void
    {
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $loader = new Loader();
        $loader->addFixture(new ProductFixtures());
        $loader->addFixture(new UserFixtures($passwordHasher));

        $purger = new ORMPurger($this->entityManager);

        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->execute($loader->getFixtures());
    }
}
