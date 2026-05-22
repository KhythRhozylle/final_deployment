<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * @return list<Order>
     */
    public function findByCustomerEmail(string $email): array
    {
        return $this->createQueryBuilder('o')
            ->innerJoin('o.customer', 'c')
            ->andWhere('LOWER(c.email) = LOWER(:email)')
            ->andWhere('o.source = :source')
            ->setParameter('email', trim($email))
            ->setParameter('source', 'mobile')
            ->orderBy('o.orderDate', 'DESC')
            ->addOrderBy('o.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Order>
     */
    public function findByGroupIdAndEmail(string $orderGroupId, string $email): array
    {
        return $this->createQueryBuilder('o')
            ->innerJoin('o.customer', 'c')
            ->andWhere('o.orderGroupId = :gid')
            ->andWhere('LOWER(c.email) = LOWER(:email)')
            ->setParameter('gid', $orderGroupId)
            ->setParameter('email', trim($email))
            ->orderBy('o.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Order>
     */
    public function findByOrderGroupId(string $orderGroupId): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.orderGroupId = :gid')
            ->setParameter('gid', $orderGroupId)
            ->orderBy('o.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<string>
     */
    public function findPendingMobileOrderGroupIds(): array
    {
        return $this->findPendingMobileGroupIds();
    }

    public function findPendingMobileGroupIds(): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('DISTINCT o.orderGroupId')
            ->andWhere('o.source = :source')
            ->andWhere('o.status = :status')
            ->andWhere('o.orderGroupId IS NOT NULL')
            ->setParameter('source', 'mobile')
            ->setParameter('status', 'pending')
            ->orderBy('o.orderGroupId', 'DESC')
            ->getQuery()
            ->getSingleColumnResult();

        return array_values(array_filter($rows));
    }

    //    /**
    //     * @return Order[] Returns an array of Order objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Order
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
