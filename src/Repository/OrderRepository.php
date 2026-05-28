<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
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
            ->select('o.orderGroupId AS gid')
            ->addSelect('MAX(o.orderDate) AS HIDDEN maxDate')
            ->andWhere('o.source = :source')
            ->andWhere('o.status = :status')
            ->andWhere('o.orderGroupId IS NOT NULL')
            ->setParameter('source', 'mobile')
            ->setParameter('status', 'pending')
            ->groupBy('o.orderGroupId')
            ->orderBy('maxDate', 'DESC')
            ->addOrderBy('o.orderGroupId', 'DESC')
            ->getQuery()
            ->getScalarResult();

        return array_values(array_filter(array_column($rows, 'gid')));
    }

    /**
     * Admin orders table: newest first (date, then id).
     *
     * @return list<Order>
     */
    public function findForAdminListing(?User $staff = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.customer', 'c')
            ->addSelect('c')
            ->orderBy('o.orderDate', 'DESC')
            ->addOrderBy('o.id', 'DESC');

        if ($staff instanceof User) {
            $qb->andWhere('o.createdBy = :staff')
                ->setParameter('staff', $staff);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Changes whenever rows are added/updated (used for admin live polling).
     */
    public function computeLiveRevision(): int
    {
        $sql = <<<'SQL'
            SELECT
                COUNT(*) AS cnt,
                COALESCE(MAX(id), 0) AS max_id,
                COALESCE(UNIX_TIMESTAMP(MAX(order_date)), 0) AS max_ts,
                COALESCE(SUM(CRC32(CONCAT(
                    id, '|', status, '|', COALESCE(payment_status, ''), '|', COALESCE(order_group_id, '')
                ))), 0) AS state_sum
            FROM `order`
            SQL;

        $row = $this->getEntityManager()->getConnection()->fetchAssociative($sql);

        return crc32(sprintf(
            '%s:%s:%s:%s',
            $row['cnt'] ?? 0,
            $row['max_id'] ?? 0,
            $row['max_ts'] ?? 0,
            $row['state_sum'] ?? 0,
        )) & 0x7FFFFFFF;
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

        $byDay = $this->sumRevenueGroupedBy(
            $since,
            static fn (\DateTimeInterface $date): string => $date->format('Y-m-d'),
        );

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

        $byMonth = $this->sumRevenueGroupedBy(
            $since,
            static fn (\DateTimeInterface $date): string => $date->format('Y-m'),
        );

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

    /**
     * @param callable(\DateTimeInterface): string $bucketKey
     *
     * @return array<string, float>
     */
    private function sumRevenueGroupedBy(\DateTimeImmutable $since, callable $bucketKey): array
    {
        $orders = $this->createQueryBuilder('o')
            ->andWhere('o.orderDate >= :since')
            ->andWhere('LOWER(o.status) NOT IN (:excluded)')
            ->setParameter('since', $since)
            ->setParameter('excluded', ['cancelled', 'rejected'])
            ->getQuery()
            ->getResult();

        $totals = [];
        foreach ($orders as $order) {
            $orderDate = $order->getOrderDate();
            if (!$orderDate instanceof \DateTimeInterface) {
                continue;
            }

            $key = $bucketKey($orderDate);
            $lineTotal = (float) $order->getPrice() * (float) $order->getQuantity();
            $totals[$key] = ($totals[$key] ?? 0.0) + $lineTotal;
        }

        return $totals;
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
