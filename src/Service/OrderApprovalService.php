<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class OrderApprovalService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderRepository $orderRepository,
        private ProductRepository $productRepository,
        private OrderLiveRevisionService $orderLiveRevision,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildGroupSummary(string $orderGroupId): array
    {
        $payload = $this->buildReviewPayload($orderGroupId);
        if ($payload === null) {
            throw new \InvalidArgumentException('Mobile order group not found.');
        }

        return $payload;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function approveGroup(string $orderGroupId, ?User $admin = null): int
    {
        $result = $this->approveGroupInternal($orderGroupId, $admin);
        if (!$result['approved']) {
            throw new \InvalidArgumentException($result['message']);
        }

        return $result['lineCount'];
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function rejectGroup(string $orderGroupId, ?User $admin = null): int
    {
        unset($admin);
        $result = $this->rejectGroupInternal($orderGroupId);
        if (!$result['rejected']) {
            throw new \InvalidArgumentException($result['message']);
        }

        return $result['lineCount'];
    }

    /**
     * Accept an order line, or the whole group when orderGroupId is set.
     *
     * @throws \InvalidArgumentException
     */
    public function acceptOrder(Order $order, ?User $admin = null): int
    {
        if ($order->getOrderGroupId()) {
            return $this->approveGroup($order->getOrderGroupId(), $admin);
        }

        $result = $this->approveOrdersInternal([$order], $admin);
        if (!$result['approved']) {
            throw new \InvalidArgumentException($result['message']);
        }

        return $result['lineCount'];
    }

    /**
     * Cancel an order line, or the whole group when orderGroupId is set.
     *
     * @throws \InvalidArgumentException
     */
    public function cancelOrder(Order $order): int
    {
        if ($order->getOrderGroupId()) {
            return $this->rejectGroup($order->getOrderGroupId());
        }

        $result = $this->rejectOrdersInternal([$order]);
        if (!$result['rejected']) {
            throw new \InvalidArgumentException($result['message']);
        }

        return $result['lineCount'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPendingMobileGroups(): array
    {
        $groups = $this->orderRepository->findPendingMobileGroupIds();
        $result = [];
        foreach ($groups as $groupId) {
            $payload = $this->buildReviewPayload($groupId);
            if ($payload) {
                $result[] = $payload;
            }
        }

        return $result;
    }

    /**
     * @return list<Order>
     */
    public function getOrdersInGroup(string $orderGroupId): array
    {
        return $this->orderRepository->findByOrderGroupId($orderGroupId);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildReviewPayload(string $orderGroupId): ?array
    {
        $orders = $this->getOrdersInGroup($orderGroupId);
        if ($orders === []) {
            return null;
        }

        $first = $orders[0];
        $customer = $first->getCustomer();
        $lines = [];
        $total = 0.0;
        $canApprove = true;
        $issues = [];

        foreach ($orders as $order) {
            $stockInfo = $this->resolveStockForLine($order);
            $lineTotal = (float) $order->getPrice() * (float) $order->getQuantity();
            $total += $lineTotal;

            if (!$stockInfo['sufficient']) {
                $canApprove = false;
                $issues[] = $stockInfo['message'];
            }

            $lines[] = [
                'orderId' => $order->getId(),
                'productName' => $order->getProductName(),
                'quantity' => (float) $order->getQuantity(),
                'price' => (float) $order->getPrice(),
                'lineTotal' => round($lineTotal, 2),
                'availableStock' => $stockInfo['available'],
                'sufficient' => $stockInfo['sufficient'],
                'stockDeducted' => $order->isStockDeducted(),
            ];
        }

        return [
            'orderGroupId' => $orderGroupId,
            'status' => MobileOrderService::resolveGroupStatus($orders),
            'source' => $first->getSource(),
            'orderDate' => $first->getOrderDate(),
            'notes' => $first->getNotes(),
            'total' => round($total, 2),
            'canApprove' => $canApprove && $first->getStatus() === MobileOrderService::STATUS_PENDING,
            'issues' => $issues,
            'paymentStatus' => $first->getPaymentStatus(),
            'paymentMethod' => $first->getPaymentMethod(),
            'paymentProofPath' => $first->getPaymentProofPath(),
            'referenceNumber' => $first->getReferenceNumber(),
            'lines' => $lines,
            'customer' => $customer ? [
                'name' => $customer->getName(),
                'email' => $customer->getEmail(),
                'phone' => $customer->getPhone(),
                'address' => $customer->getAddress(),
                'deliveryLocation' => $customer->getDeliveryLocation(),
                'cityProvince' => $customer->getCityProvince(),
            ] : null,
        ];
    }

    /**
     * @return array{approved: bool, message: string, lineCount: int}
     */
    private function approveGroupInternal(string $orderGroupId, ?User $admin = null): array
    {
        $orders = $this->getOrdersInGroup($orderGroupId);
        if ($orders === []) {
            return ['approved' => false, 'message' => 'Order not found.', 'lineCount' => 0];
        }

        if ($orders[0]->getStatus() !== MobileOrderService::STATUS_PENDING) {
            return ['approved' => false, 'message' => 'Only pending orders can be approved.', 'lineCount' => 0];
        }

        $review = $this->buildReviewPayload($orderGroupId);
        if (!$review['canApprove']) {
            return [
                'approved' => false,
                'message' => 'Cannot approve: insufficient stock. '.implode(' ', $review['issues']),
                'lineCount' => 0,
            ];
        }

        $result = $this->approveOrdersInternal($orders, $admin);
        if (!$result['approved']) {
            return $result;
        }

        $this->syncMobileGroupStatus($orderGroupId, MobileOrderService::STATUS_CONFIRMED);

        return $result;
    }

    /**
     * @param list<Order> $orders
     *
     * @return array{approved: bool, message: string, lineCount: int}
     */
    private function approveOrdersInternal(array $orders, ?User $admin = null): array
    {
        if ($orders === []) {
            return ['approved' => false, 'message' => 'Order not found.', 'lineCount' => 0];
        }

        if ($orders[0]->getStatus() !== MobileOrderService::STATUS_PENDING) {
            return ['approved' => false, 'message' => 'Only pending orders can be approved.', 'lineCount' => 0];
        }

        foreach ($orders as $order) {
            $product = $this->findProductForOrder($order);
            if (!$product instanceof Product) {
                return [
                    'approved' => false,
                    'message' => sprintf('Product not found for line: %s', $order->getProductName()),
                    'lineCount' => 0,
                ];
            }

            $qty = (int) $order->getQuantity();
            if (!$order->isStockDeducted()) {
                if ($product->getStock() < $qty) {
                    return [
                        'approved' => false,
                        'message' => sprintf(
                            'Not enough stock for "%s" (need %d, have %d).',
                            $product->getName(),
                            $qty,
                            $product->getStock()
                        ),
                        'lineCount' => 0,
                    ];
                }
                $product->setStock($product->getStock() - $qty);
                $order->setStockDeducted(true);
                $this->entityManager->persist($product);
            }

            $order->setStatus(MobileOrderService::STATUS_CONFIRMED);
            if ($admin) {
                $order->setCreatedBy($admin);
            }
            $this->entityManager->persist($order);
        }

        $this->entityManager->flush();
        $this->orderLiveRevision->bump();

        return [
            'approved' => true,
            'message' => 'Order approved. Stock deducted and status set to Confirmed.',
            'lineCount' => count($orders),
        ];
    }

    /**
     * @return array{rejected: bool, message: string, lineCount: int}
     */
    private function rejectGroupInternal(string $orderGroupId): array
    {
        $orders = $this->getOrdersInGroup($orderGroupId);
        if ($orders === []) {
            return ['rejected' => false, 'message' => 'Order not found.', 'lineCount' => 0];
        }

        if ($orders[0]->getStatus() !== MobileOrderService::STATUS_PENDING) {
            return ['rejected' => false, 'message' => 'Only pending orders can be rejected.', 'lineCount' => 0];
        }

        $result = $this->rejectOrdersInternal($orders);
        if (!$result['rejected']) {
            return $result;
        }

        $this->syncMobileGroupStatus($orderGroupId, MobileOrderService::STATUS_CANCELLED);

        return $result;
    }

    /**
     * @param list<Order> $orders
     *
     * @return array{rejected: bool, message: string, lineCount: int}
     */
    private function rejectOrdersInternal(array $orders): array
    {
        if ($orders === []) {
            return ['rejected' => false, 'message' => 'Order not found.', 'lineCount' => 0];
        }

        if ($orders[0]->getStatus() !== MobileOrderService::STATUS_PENDING) {
            return ['rejected' => false, 'message' => 'Only pending orders can be rejected.', 'lineCount' => 0];
        }

        foreach ($orders as $order) {
            if ($order->isStockDeducted()) {
                $product = $this->findProductForOrder($order);
                if ($product instanceof Product) {
                    $product->setStock($product->getStock() + (int) $order->getQuantity());
                    $this->entityManager->persist($product);
                }
                $order->setStockDeducted(false);
            }
            $order->setStatus(MobileOrderService::STATUS_CANCELLED);
            $this->entityManager->persist($order);
        }

        $this->entityManager->flush();
        $this->orderLiveRevision->bump();

        return [
            'rejected' => true,
            'message' => 'Order rejected.',
            'lineCount' => count($orders),
        ];
    }

    /**
     * @return array{available: int, sufficient: bool, message: string}
     */
    private function resolveStockForLine(Order $order): array
    {
        $product = $this->findProductForOrder($order);
        $qty = (int) $order->getQuantity();

        if (!$product instanceof Product) {
            return [
                'available' => 0,
                'sufficient' => false,
                'message' => sprintf('Product "%s" not found in catalog.', $order->getProductName()),
            ];
        }

        $available = $product->getStock();
        $sufficient = $available >= $qty;

        return [
            'available' => $available,
            'sufficient' => $sufficient,
            'message' => $sufficient
                ? ''
                : sprintf('"%s" needs %d but only %d in stock.', $product->getName(), $qty, $available),
        ];
    }

    private function findProductForOrder(Order $order): ?Product
    {
        if ($order->getProductId()) {
            $byId = $this->productRepository->find($order->getProductId());
            if ($byId instanceof Product) {
                return $byId;
            }
        }

        return $this->productRepository->findOneBy(['name' => $order->getProductName()]);
    }

    /**
     * Keep every line in a mobile order group on the same status (mobile app reads the group).
     */
    public function syncMobileGroupStatus(string $orderGroupId, string $status): void
    {
        $normalized = MobileOrderService::normalizeStatus($status);
        $changed = false;
        foreach ($this->getOrdersInGroup($orderGroupId) as $order) {
            if ($order->getSource() !== 'mobile') {
                continue;
            }
            $order->setStatus($normalized);
            $this->entityManager->persist($order);
            $changed = true;
        }

        if ($changed) {
            $this->entityManager->flush();
            $this->orderLiveRevision->bump();
        }
    }
}
