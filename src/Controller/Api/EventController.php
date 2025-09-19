<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Repository\RegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class EventController extends AbstractController
{
    #[Route('/api/event', methods: ['GET'])]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        $events = $em->getRepository(Event::class)->findAll();

        $out = array_map(function (Event $e) {
            return [
                'id' => $e->getId(),
                'name' => $e->getName(),
                'capacity' => $e->getCapacity(),
                'startAt' => $e->getStartAt()->format(\DateTimeInterface::ATOM),
                ];
        }, $events);

        return $this->json($out);
    }

    #[Route('/api/event/{id}', methods: ['GET'])]
    public function show(Event $event): JsonResponse
    {
        return $this->json([
            'id' => $event->getId(),
            'name' => $event->getName(),
            'capacity' => $event->getCapacity(),
            'startAt' => $event->getStartAt()->format(\DateTimeInterface::ATOM),
        ]);
    }
    #[Route('/api/event/create', methods: ['POST'])]
    public function create(Request $request,
                           SerializerInterface $serializer,
                           EntityManagerInterface $em,
                           ValidatorInterface $validator) : JsonResponse
    {
        $content = $request->getContent();
        $event = $serializer->deserialize($content, Event::class, "json");

        $errors = $validator->validate($event);

        if(count($errors) != 0){
            $error_messages = [];

            foreach ($errors as $error){
                $error_messages[$error->getPropertyPath()][] = $error->getMessage();
            }
            return $this->json(["errors" => $error_messages], 422);
        }

        $em->persist($event);
        $em->flush();

        return $this->json($event, 201);
    }

    #[Route('/api/event/{id}', methods: ['PUT', 'PATCH'])]
    public function update(Request $request,
                            Event $event,
                            SerializerInterface $serializer,
                            EntityManagerInterface $em) : JsonResponse
    {
        $serializer->deserialize($request->getContent(), Event::class, "json",["object_to_populate" => $event]);
        $em->flush();
        return $this->json($event);
    }

    #[Route('/api/event/{id}', methods: ["DELETE"])]
    public function delete(EntityManagerInterface $em,
                            Event $event) : JsonResponse
    {
        $em->remove($event);

        $em->flush();

        return $this->json(null, 204 );
    }
    #[Route('/api/events/{id}/stats', methods: ['GET'])]
    public function stats(
        Event $event,
        RegistrationRepository $registrations
    ): JsonResponse {
        $activeCount = $registrations->countActiveForEvent($event);
        $waitlistCount = $registrations->countWaitlistedForEvent($event);
        return $this->json([
            'activeCount' => $activeCount,
            'waitlistCount' => $waitlistCount,
        ]);
    }
}
