<?php

namespace App\DataFixtures;

use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Event;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
         $event = new Event();
         $manager->persist($event);
         $event->setName('Walk');
         $event->setCapacity(1);
         $event->setStartAt( new DateTimeImmutable('now'));

         $manager->persist($event);
         $manager->flush();
    }
}
