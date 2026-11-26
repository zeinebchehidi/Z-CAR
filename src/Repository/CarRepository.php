<?php

namespace App\Repository;

use App\Entity\Car;
use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Car>
 */
class CarRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Car::class);
    }

    /**
     * Available cars matching the filters of CarSearchType. Empty filters are ignored.
     *
     * @param array{category?: ?\App\Entity\Category, fuelType?: ?\App\Enum\FuelType,
     *     transmission?: ?\App\Enum\Transmission, minPrice?: ?float, maxPrice?: ?float,
     *     start?: ?\DateTimeImmutable, end?: ?\DateTimeImmutable} $filters
     *
     * @return Car[]
     */
    public function search(array $filters): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.isAvailable = true')
            ->orderBy('c.pricePerDay', 'ASC');

        if (!empty($filters['category'])) {
            $qb->andWhere('c.category = :category')->setParameter('category', $filters['category']);
        }
        if (!empty($filters['fuelType'])) {
            $qb->andWhere('c.fuelType = :fuel')->setParameter('fuel', $filters['fuelType']->value);
        }
        if (!empty($filters['transmission'])) {
            $qb->andWhere('c.transmission = :transmission')->setParameter('transmission', $filters['transmission']->value);
        }
        if (isset($filters['minPrice'])) {
            $qb->andWhere('c.pricePerDay >= :minPrice')->setParameter('minPrice', $filters['minPrice']);
        }
        if (isset($filters['maxPrice'])) {
            $qb->andWhere('c.pricePerDay <= :maxPrice')->setParameter('maxPrice', $filters['maxPrice']);
        }

        // Dates: hide cars already booked in that period (the rule lives in ReservationRepository).
        if (!empty($filters['start']) && !empty($filters['end'])) {
            $bookedIds = $this->getEntityManager()->getRepository(Reservation::class)
                ->findBookedCarIds($filters['start'], $filters['end']);
            if ($bookedIds) {
                $qb->andWhere('c.id NOT IN (:bookedIds)')->setParameter('bookedIds', $bookedIds);
            }
        }

        return $qb->getQuery()->getResult();
    }
}
