<?php
// src/DataFixtures/ConseilFixtures.php
namespace App\DataFixtures;

use App\Entity\Conseil;
use App\Entity\Mois;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

/**
 * @method object getReference(string $name)
 */
class ConseilFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [
            MoisFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $c1 = new Conseil();
        $c1->setContent('Taillez les arbustes avant le printemps.');
        $c1->addMois($this->getReference('mois_2', Mois::class));
        $c1->addMois($this->getReference('mois_3', Mois::class));
        $c1->setCreatedAt(new \DateTimeImmutable());
        $c1->setUpdatedAt(new \DateTimeImmutable());

        $c2 = new Conseil();
        $c2->setContent('Arrosez de préférence tôt le matin ou le soir.');
        $c2->addMois($this->getReference('mois_6', Mois::class));
        $c2->addMois($this->getReference('mois_7', Mois::class));
        $c2->addMois($this->getReference('mois_8', Mois::class));
        $c2->setCreatedAt(new \DateTimeImmutable());
        $c2->setUpdatedAt(new \DateTimeImmutable());

        $manager->persist($c1);
        $manager->persist($c2);
        $manager->flush();
    }
}
