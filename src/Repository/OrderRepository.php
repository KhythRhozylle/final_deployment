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
        $orders = $this->findByOrderGroupId($orderGroupId);
        if ($orders === []) {
            return [];
        }

        $normalizedEmail = strtolower(trim($email));
        $customer = $orders[0]->getCustomer();
        if ($customer === null) {
            return [];
        }

        if (strtolower(trim((string) $customer->getEmail())) !== $normalizedEmail) {
            return [];
        }

        return $orders;
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

    public function getTotalRevenue(): float
    {
        return (float) $this->createQueryBuilder('o')
            ->select('COALESCE(SUM(o.price * o.quantity), 0)')
            ->andWhere('LOWER(o.status) NOT IN (:excluded)')
            ->setParameter('excluded', ['cancelled', 'rejected'])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<array{day: string, total: float}>
     */
    public function getDailyRevenue(int $days = 7): array
    {
        $since = new \DateTimeImmutable(sprintf('-%d days', max(1, $days - 1)));
        $since = $since->setTime(0, 0, 0);

        $rows = $this->createQueryBuilder('o')
            ->select('DATE(o.orderDate) AS dayKey')
            ->addSelect('COALESCE(SUM(o.price * o.quantity), 0) AS total')
            ->andWhere('o.orderDate >= :since')
            ->andWhere('LOWER(o.status) NOT IN (:excluded)')
            ->setParameter('since', $since)
            ->setParameter('excluded', ['cancelled', 'rejected'])
            ->groupBy('dayKey')
            ->orderBy('dayKey', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $byDay = [];
        foreach ($rows as $row) {
            $key = (string) ($row['dayKey'] ?? '');
            if ($key !== '') {
                $byDay[$key] = (float) ($row['total'] ?? 0);
            }
        }

        $result = [];
        for ($i = 0; $i < $days; ++$i) {
            $date = $since->modify(sprintf('+%d days', $i));
            $key = $date->format('Y-m-d');
            $result[] = [
                'day' => $date->format('D'),
                'total' => $byDay[$key] ?? 0.0,
            ];
        }

        return $result;
    }

    /**
     * @return list<array{month: string, total: float}>
     */
    public function getMonthlyRevenue(int $months = 6): array
    {
        $since = new \DateTimeImmutable('first day of this month');
        $since = $since->modify(sprintf('-%d months', max(0, $months - 1)))->setTime(0, 0, 0);

        $rows = $this->createQueryBuilder('o')
            ->select("DATE_FORMAT(o.orderDate, '%Y-%m') AS monthKey")
            ->addSelect('COALESCE(SUM(o.price * o.quantity), 0) AS total')
            ->andWhere('o.orderDate >= :since')
            ->andWhere('LOWER(o.status) NOT IN (:excluded)')
            ->setParameter('since', $since)
            ->setParameter('excluded', ['cancelled', 'rejected'])
            ->groupBy('monthKey')
            ->orderBy('monthKey', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $byMonth = [];
        foreach ($rows as $row) {
            $key = (string) ($row['monthKey'] ?? '');
            if ($key !== '') {
                $byMonth[$key] = (float) ($row['total'] ?? 0);
            }
        }

        $result = [];
        for ($i = 0; $i < $months; ++$i) {
            $month = $since->modify(sprintf('+%d months', $i));
            $key = $month->format('Y-m');
            $result[] = [
                'month' => $month->format('M'),
                'total' => $byMonth[$key] ?? 0.0,
            ];
        }

        return $result;
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->count(['status' => $status]);
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
