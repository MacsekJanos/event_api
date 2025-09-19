<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\Registration;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Registration>
 */
class RegistrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Registration::class);
    }

    public function existsForUserAndEvent(User $user, Event $event): bool
    {
        return (bool) $this->createQueryBuilder('r')
            ->select('1')
            ->andWhere('r.user = :user')
            ->andWhere('r.event = :event')
            ->setMaxResults(1)
            ->setParameter('user', $user)
            ->setParameter('event', $event)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countActiveForEvent(Event $event): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.event = :event')
            ->andWhere('r.isConfirmed = true')
            ->setParameter('event', $event)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function nextWaitlistPosition(Event $event): int
    {
        $max = (int) $this->createQueryBuilder('r')
            ->select('COALESCE(MAX(r.waitlistPosition), 0)')
            ->andWhere('r.event = :event')
            ->andWhere('r.isConfirmed = false')
            ->setParameter('event', $event)
            ->getQuery()
            ->getSingleScalarResult();

        return $max + 1;
    }

    public function findNextWaitlisted(Event $event): ?Registration
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.event = :event')
            ->andWhere('r.isConfirmed = false')
            ->andWhere('r.waitlistPosition IS NOT NULL')
            ->orderBy('r.waitlistPosition', 'ASC')
            ->setMaxResults(1)
            ->setParameter('event', $event)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function compactWaitlistAfter(Event $event, int $oldPos): int
    {
        return $this->createQueryBuilder('r')
            ->update()
            ->set('r.waitlistPosition', 'r.waitlistPosition - 1')
            ->andWhere('r.event = :event')
            ->andWhere('r.isConfirmed = false')
            ->andWhere('r.waitlistPosition IS NOT NULL')
            ->andWhere('r.waitlistPosition > :oldPos')
            ->setParameter('oldPos', $oldPos)
            ->setParameter('event', $event)
            ->getQuery()
            ->execute();
    }

    public function countWaitlistedForEvent(Event $event): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.event = :event')
            ->andWhere('r.isConfirmed = false')
            ->andWhere('r.waitlistPosition IS NOT NULL')
            ->setParameter('event', $event)
            ->getQuery()
            ->getSingleScalarResult();
    }
    //    /**
    //     * @return Registration[] Returns an array of Registration objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Registration
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
