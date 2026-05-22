<?php

namespace App\DataFixtures;

/**
 * Shared reference names for cross-fixture relations (maps original DB ids).
 */
final class FixtureReferences
{
    public static function user(int $id): string
    {
        return 'user_' . $id;
    }

    public static function category(int $id): string
    {
        return 'category_' . $id;
    }

    public static function customer(int $id): string
    {
        return 'customer_' . $id;
    }

    public static function product(int $id): string
    {
        return 'product_' . $id;
    }

    public static function order(int $id): string
    {
        return 'order_' . $id;
    }

    public static function activityLog(int $id): string
    {
        return 'activity_log_' . $id;
    }
}
