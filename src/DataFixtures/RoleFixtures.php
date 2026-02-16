<?php

namespace App\DataFixtures;

use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RoleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $roles = ['ROLE_USER', 'ROLE_ADMIN'];

        foreach ($roles as $roleName) {
            $role = new Role();
            $role->setName($roleName);

            $manager->persist($role);

            $this->addReference($roleName, $role);
        }

        $manager->flush();
    }
}