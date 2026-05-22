<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\Product;
use App\Repository\CustomerRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class MobileOrderService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    public const MOBILE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_PREPARING,
        self::STATUS_OUT_FOR_DELIVERY,
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CustomerRepository $customerRepository,
        private OrderRepository $orderRepository,
        private ProductRepository $productRepository,
    ) {}

    /**
     * @param array<string, mixed> $customerInput
     * @param list<array{productId?: int, name: string, quantity: int|float, price: float}> $items
     *
     * @return array{orderGroupId: string, total: float, itemCount: int}
     */
    public function placeOrder(array $customerInput, array $items): array
    {
        if ($items === []) {
            throw new \InvalidArgumentException('Cart is empty.');
        }

        $email = trim((string) ($customerInput['email'] ?? ''));
        $fullName = trim((string) ($customerInput['fullName'] ?? ''));
        $contactNumber = trim((string) ($customerInput['contactNumber'] ?? ''));
        $completeAddress = trim((string) ($customerInput['completeAddress'] ?? ''));
        $deliveryLocation = trim((string) ($customerInput['deliveryLocation'] ?? ''));
        $cityProvince = trim((string) ($customerInput['cityProvince'] ?? ''));
        $additionalNotes = trim((string) ($customerInput['additionalNotes'] ?? ''));

        if ($email === '' || $fullName === '' || $contactNumber === '' || $completeAddress === '' || $deliveryLocation === '' || $cityProvince === '') {
            throw new \InvalidArgumentException('All required customer fields must be provided.');
        }

        $customer = $this->customerRepository->findOneBy(['email' => $email]);
        if (!$customer) {
            $customer = new Customer();
            $customer->setEmail(strtolower($email));
            $customer->setUsername($this->uniqueUsernameFromEmail($email));
        }

        $customer->setName($fullName);
        $customer->setCustomerName($fullName);
        $customer->setPhone($contactNumber);
        $customer->setAddress($completeAddress);
        $customer->setDeliveryLocation($deliveryLocation);
        $customer->setCityProvince($cityProvince);

        $this->entityManager->persist($customer);

        $orderGroupId = Uuid::v4()->toRfc4122();
        $total = 0.0;
        $notes = $additionalNotes !== '' ? $additionalNotes : null;

        foreach ($items as $line) {
            $productName = trim((string) ($line['name'] ?? ''));
            $quantity = (float) ($line['quantity'] ?? 0);
            $price = (float) ($line['price'] ?? 0);
            $productId = isset($line['productId']) ? (int) $line['productId'] : null;

            if ($productName === '' || $quantity <= 0) {
                throw new \InvalidArgumentException('Each cart item must have a name and quantity.');
            }

            $resolvedProductId = null;
            if ($productId) {
                $product = $this->productRepository->find($productId);
                if ($product instanceof Product) {
                    if ($product->getStock() < (int) $quantity) {
                        throw new \InvalidArgumentException(sprintf(
                            'Not enough stock for "%s". Available: %d.',
                            $product->getName(),
                            $product->getStock()
                        ));
                    }
                    $productName = $product->getName();
                    $resolvedProductId = $product->getId();
                    if ($price <= 0) {
                        $price = (float) $product->getPrice();
                    }
                }
            }

            $order = new Order();
            $order->setCustomer($customer);
            $order->setProductName($productName);
            $order->setQuantity($quantity);
            $order->setPrice($price);
            $order->setStatus(self::STATUS_PENDING);
            $order->setOrderGroupId($orderGroupId);
            $order->setNotes($notes);
            $order->setSource('mobile');
            $order->setProductId($resolvedProductId);
            $order->setStockDeducted(false);
            $order->setOrderDate(new \DateTime());

            $this->entityManager->persist($order);
            $total += $price * $quantity;
        }

        $this->entityManager->flush();

        return [
            'orderGroupId' => $orderGroupId,
            'total' => round($total, 2),
            'itemCount' => count($items),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listOrdersForEmail(string $email): array
    {
        $email = trim($email);
        if ($email === '') {
            return [];
        }

        $orders = $this->orderRepository->findByCustomerEmail($email);
        return $this->groupOrders($orders);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getOrderGroup(string $orderGroupId, string $email): ?array
    {
        $orders = $this->orderRepository->findByGroupIdAndEmail($orderGroupId, $email);
        if ($orders === []) {
            return null;
        }

        $grouped = $this->groupOrders($orders);

        return $grouped[0] ?? null;
    }

    /**
     * @param list<Order> $orders
     *
     * @return list<array<string, mixed>>
     */
    /**
     * @param list<Order> $orders
     */
    public static function resolveGroupStatus(array $orders): string
    {
        if ($orders === []) {
            return self::STATUS_PENDING;
        }

        $statuses = array_map(
            static fn (Order $order) => self::normalizeStatus($order->getStatus()),
            $orders,
        );
        $unique = array_values(array_unique($statuses));

        if (\count($unique) === 1) {
            return $unique[0];
        }

        // Mixed line statuses: show the most advanced step (e.g. confirmed beats pending).
        $priority = [
            self::STATUS_CANCELLED => 0,
            self::STATUS_PENDING => 1,
            'processing' => 2,
            self::STATUS_CONFIRMED => 3,
            self::STATUS_PREPARING => 4,
            self::STATUS_OUT_FOR_DELIVERY => 5,
            self::STATUS_DELIVERED => 6,
        ];

        $best = self::STATUS_PENDING;
        $bestRank = -1;
        foreach ($unique as $status) {
            if ($status === self::STATUS_CANCELLED && \count($unique) > 1) {
                continue;
            }
            $rank = $priority[$status] ?? 1;
            if ($rank > $bestRank) {
                $bestRank = $rank;
                $best = $status;
            }
        }

        return $best;
    }

    public static function normalizeStatus(?string $status): string
    {
        $value = strtolower(trim((string) $status));

        return match ($value) {
            'processing' => self::STATUS_CONFIRMED,
            'completed', 'complete', 'shipped' => self::STATUS_DELIVERED,
            default => $value !== '' ? $value : self::STATUS_PENDING,
        };
    }

    private function groupOrders(array $orders): array
    {
        /** @var array<string, list<Order>> $byGroup */
        $byGroup = [];

        foreach ($orders as $order) {
            $gid = $order->getOrderGroupId() ?? 'legacy-' . $order->getId();
            $byGroup[$gid][] = $order;
        }

        $groups = [];

        foreach ($byGroup as $gid => $groupOrders) {
            $first = $groupOrders[0];
            $customer = $first->getCustomer();
            $group = [
                'orderGroupId' => $first->getOrderGroupId(),
                'status' => self::resolveGroupStatus($groupOrders),
                'orderDate' => $first->getOrderDate()?->format(\DateTimeInterface::ATOM),
                'source' => $first->getSource(),
                'notes' => $first->getNotes(),
                'total' => 0.0,
                'items' => [],
                'customer' => $customer ? [
                    'fullName' => $customer->getName(),
                    'email' => $customer->getEmail(),
                    'contactNumber' => $customer->getPhone(),
                    'completeAddress' => $customer->getAddress(),
                    'deliveryLocation' => $customer->getDeliveryLocation(),
                    'cityProvince' => $customer->getCityProvince(),
                ] : null,
            ];

            foreach ($groupOrders as $order) {
                $lineTotal = (float) $order->getPrice() * (float) $order->getQuantity();
                $group['total'] += $lineTotal;
                $group['items'][] = [
                    'id' => $order->getId(),
                    'productName' => $order->getProductName(),
                    'quantity' => (float) $order->getQuantity(),
                    'price' => (float) $order->getPrice(),
                    'lineTotal' => round($lineTotal, 2),
                    'status' => self::normalizeStatus($order->getStatus()),
                ];
            }

            $group['total'] = round($group['total'], 2);
            $groups[] = $group;
        }

        usort($groups, static fn ($a, $b) => strcmp((string) ($b['orderDate'] ?? ''), (string) ($a['orderDate'] ?? '')));

        return $groups;
    }

    private function uniqueUsernameFromEmail(string $email): string
    {
        $base = preg_replace('/[^a-z0-9]/', '', strtolower(explode('@', $email)[0] ?? 'customer')) ?: 'customer';
        $candidate = $base;
        $i = 0;
        while ($this->customerRepository->findOneBy(['username' => $candidate])) {
            ++$i;
            $candidate = $base . $i;
        }

        return $candidate;
    }
}
