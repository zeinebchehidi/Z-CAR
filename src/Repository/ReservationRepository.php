<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Enum\ReservationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Business rule 1: ids of the cars already booked for [start, end], i.e. that have a
     * non-cancelled reservation with start_date <= :end AND end_date >= :start.
     *
     * @return int[]
     */
    public function findBookedCarIds(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->createQueryBuilder('r')
            ->select('DISTINCT IDENTITY(r.car)')
            ->andWhere('r.status != :cancelled')
            ->andWhere('r.startDate <= :end')
            ->andWhere('r.endDate >= :start')
            ->setParameter('cancelled', ReservationStatus::Cancelled->value)
            ->setParameter('start', $start, Types::DATE_IMMUTABLE)
            ->setParameter('end', $end, Types::DATE_IMMUTABLE)
            ->getQuery()
            ->getSingleColumnResult();
    }
}
