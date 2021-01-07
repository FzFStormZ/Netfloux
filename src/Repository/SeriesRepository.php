<?php

namespace App\Repository;

use App\Entity\Series;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Series|null find($id, $lockMode = null, $lockVersion = null)
 * @method Series|null findOneBy(array $criteria, array $orderBy = null)
 * @method Series[]    findAll()
 * @method Series[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SeriesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Series::class);
    }

    /**
     * @return Series[] Returns an array of Series objects
     */

    public function findCustom($title, $country, $genre)
    {
        return $this->createQueryBuilder('s')
            ->join('s.genre', 'g')
            ->join('s.country', 'c')
            ->where('s.title LIKE :title')
            ->andWhere('g.name LIKE :genre')
            ->andWhere('c.name LIKE :country')
            ->setParameter('title', $title.'%')
            ->setParameter('genre', $genre.'%')
            ->setParameter('country', $country.'%')
            ->getQuery()
            ->getResult()
        ;
    }

    
    public function findAllAndAverage($sort)
    {
        return $this->createQueryBuilder('s')
            ->select('s.id, s.title, AVG(r.value) AS mark')
            ->leftJoin('s.ratings', 'r')
            ->groupBy('s.title')
            ->orderBy('mark', $sort)
            ->getQuery()
            ->getScalarResult();
        ;
    }
}
