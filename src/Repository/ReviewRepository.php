<?php

namespace App\Repository;

use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * Average rating of approved reviews for every car, in one query (for the car cards).
     *
     * @return array<int, float> car id => average rating
     */
    public function findAverageRatings(): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.car) AS carId', 'AVG(r.rating) AS average')
            ->andWhere('r.isApproved = true')
            ->groupBy('r.car')
            ->getQuery()
            ->getArrayResult();

        return array_map('floatval', array_column($rows, 'average', 'carId'));
    }
}
