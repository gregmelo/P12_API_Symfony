<?php
// src/DataFixtures/MoisFixtures.php

namespace App\DataFixtures;

use App\Entity\Mois;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MoisFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $mois = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars',
            4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
            7 => 'Juillet', 8 => 'Août', 9 => 'Septembre',
            10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        foreach ($mois as $num => $name) {
            $m = new Mois();
            $m->setNumber($num);
            $m->setName($name);

            $manager->persist($m);
            $this->addReference('mois_'.$num, $m);
        }

        $manager->flush();
    }
}
