<?php
// src/DataFixtures/UserFixtures.php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

/**
 * @method object getReference(string $name)
 */
class UserFixtures extends Fixture implements DependentFixtureInterface
{

public function getDependencies(): array
{
return [
RoleFixtures::class,
];
}

public function __construct(
private UserPasswordHasherInterface $hasher
) {}

public function load(ObjectManager $manager): void
{
$admin = new User();
$admin->setEmail('admin@ecogarden.fr');
$admin->setCity('Lyon');
		$admin->addRole($this->getReference('ROLE_ADMIN', Role::class));
$admin->setPassword(
$this->hasher->hashPassword($admin, 'admin123')
);
$admin->setCreatedAt(new \DateTimeImmutable());

$user = new User();
$user->setEmail('user@ecogarden.fr');
$user->setCity('Paris');
	$user->addRole($this->getReference('ROLE_USER', Role::class));
$user->setPassword(
$this->hasher->hashPassword($user, 'user123')
);
$user->setCreatedAt(new \DateTimeImmutable());

$manager->persist($admin);
$manager->persist($user);
$manager->flush();
}
}