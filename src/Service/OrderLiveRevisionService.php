<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Increments when orders change (mobile checkout, payment, admin status).
 * Admin UI polls this to refresh without manual page reload.
 */
final class OrderLiveRevisionService
{
    private const CACHE_KEY = 'admin.orders.live_revision';

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
    ) {}

    public function current(): int
    {
        $item = $this->cache->getItem(self::CACHE_KEY);

        return $item->isHit() ? (int) $item->get() : 0;
    }

    public function bump(): void
    {
        $item = $this->cache->getItem(self::CACHE_KEY);
        $item->set($this->current() + 1);
        $this->cache->save($item);
    }
}
