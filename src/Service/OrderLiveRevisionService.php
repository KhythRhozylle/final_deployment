<?php

namespace App\Service;

use App\Repository\OrderRepository;

/**
 * Revision token for admin order live polling (derived from DB — reliable on Railway).
 */
final class OrderLiveRevisionService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
    ) {}

    public function current(): int
    {
        return $this->orderRepository->computeLiveRevision();
    }

    public function bump(): void
    {
        // No-op: {@see computeLiveRevision()} reads current order state from the database.
    }
}
