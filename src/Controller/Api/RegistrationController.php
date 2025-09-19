<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\Registration;
use App\Entity\User;
use App\Repository\RegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class RegistrationController extends AbstractController
{
    #[Route('/api/events/{id}/registrations', name: 'api_event_apply', methods: ['POST'])]
    public function apply(
        Event $event,
        #[CurrentUser] User $user,
        EntityManagerInterface $em,
        RegistrationRepository $registrations
    ): JsonResponse {

        if ($registrations->existsForUserAndEvent($user, $event)) {
            return $this->json(['error' => 'already registered'], 409);
        }

        $activeCount = $registrations->countActiveForEvent($event);

        $reg = new Registration();
        $reg->setUser($user);
        $reg->setEvent($event);

        if (method_exists($event, 'getCapacity') && $activeCount < (int) $event->getCapacity()) {
            $reg->setIsConfirmed(true);
            $reg->setWaitlistPosition(null);
            $message = 'registered';
        } else {
            $reg->setIsConfirmed(false);
            $reg->setWaitlistPosition($registrations->nextWaitlistPosition($event));
            $message = 'waitlisted';
        }

        $em->persist($reg);
        $em->flush();

        return $this->json([
            'id' => $reg->getId(),
            'eventId' => $event->getId(),
            'userId' => $user->getId(),
            'confirmed' => $reg->isConfirmed(),
            'waitlistPosition' => $reg->getWaitlistPosition(),
            'message' => $message,
        ], 201);
    }

    #[Route('/api/registrations/{id}', name: 'api_registration_cancel', methods: ['DELETE'])]
    public function cancel(
        Registration $reg,
        #[CurrentUser] User $user,
        EntityManagerInterface $em,
        RegistrationRepository $registrations
    ): JsonResponse {

        if ($reg->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'forbidden'], 403);
        }

        $event = $reg->getEvent();
        $wasConfirmed = (bool) $reg->isConfirmed();

        $em->remove($reg);
        $em->flush();

        if ($wasConfirmed) {
            $next = $registrations->findNextWaitlisted($event);
            if ($next) {
                $oldPos = $next->getWaitlistPosition();
                $next->setIsConfirmed(true);
                $next->setWaitlistPosition(null);
                $em->flush();
                $registrations->compactWaitlistAfter($event, $oldPos);
            }
        }

        return new JsonResponse(null, 204);
    }
}
