<?php

namespace App\Story;

use App\Controller\Api\RegistrationController;
use App\Factory\EventFactory;
use App\Factory\RegistrationFactory;
use App\Factory\UserFactory;
use App\Repository\RegistrationRepository;
use App\Service\RegistrationManager;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

#[AsFixture(name: 'main')]
final class AppStory extends Story
{
    public function __construct(
        private RegistrationController $controller,
        private EntityManagerInterface $em,
        private RegistrationRepository $registrations
    ) {}

    private function toEntity(object $maybeProxy, EntityManagerInterface $em): object
    {
        if (method_exists($maybeProxy, 'getId')) {
            $class = get_parent_class($maybeProxy) ?: get_class($maybeProxy);
            $id = $maybeProxy->getId();
            return $em->find($class, $id) ?? $maybeProxy;
        }
        return $maybeProxy;
    }

    public function build(): void
    {
        $events = EventFactory::new()->createMany(5);
        $users  = UserFactory::new()->createMany(20);

        foreach ($events as $i => $ev) {
            $event = $this->toEntity($ev, $this->em);

            $start = $i * 4;
            $slice = array_slice($users, $start, 4);

            foreach ($slice as $u) {
                $user = $this->toEntity($u, $this->em);
                $this->controller->apply($event, $user, $this->em, $this->registrations);
            }
        }
    }
}
