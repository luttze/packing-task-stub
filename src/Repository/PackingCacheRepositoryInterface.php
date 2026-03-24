<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Packaging;
use App\Entity\PackingCache;

/**
 * Cache for packing computation results keyed by a hash of the input.
 *
 * TODO: Cache retention — the current implementation stores results indefinitely.
 *  In a production shopping cart environment this table will grow unbounded because:
 *  - Cart contents change frequently (add/remove item = new cache key)
 *  - Seasonal inventory changes invalidate old entries
 *  - Promotional bundles create short-lived combinations
 *
 *  Recommended improvements:
 *  1. TTL policy — treat entries older than e.g. 24h as stale. Check `createdAt`
 *     on read and ignore expired rows (or use a DB-level TTL if available).
 *  2. Cleanup job — scheduled cron/command to DELETE rows older than the retention
 *     window (e.g. `DELETE FROM packing_cache WHERE created_at < NOW() - INTERVAL 7 DAY`).
 *  3. Retention window — make the TTL and cleanup threshold configurable so ops
 *     can tune it based on traffic patterns without a code change.
 */
interface PackingCacheRepositoryInterface
{
    public function findByHash(string $cacheKey): ?PackingCache;
    public function saveResult(string $cacheKey, ?Packaging $result): PackingCache;
}
