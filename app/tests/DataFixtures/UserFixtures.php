<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User('admin@example.com');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setRoles([UserRole::Admin->value]);

        $user = new User('user@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'user123'));
        $user->setRoles([UserRole::User->value]);

        $manager->persist($admin);
        $manager->persist($user);

        $manager->flush();
    }
}
