<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Packaging;
use App\Entity\PackingCache;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use LogicException;

class DoctrinePackingCacheRepository implements PackingCacheRepositoryInterface
{
    public function __construct(
        private readonly EntityManager $entityManager,
    ) {}

    public function findByHash(string $cacheKey): ?PackingCache
    {
        return $this->entityManager
            ->getRepository(PackingCache::class)
            ->findOneBy(['requestHash' => $cacheKey]);
    }

    public function saveResult(string $cacheKey, ?Packaging $result): PackingCache
    {
        $cache = new PackingCache($cacheKey, $result);

        try {
            $this->entityManager->persist($cache);
            $this->entityManager->flush();

            return $cache;
        } catch (UniqueConstraintViolationException) {
            $this->entityManager->detach($cache);

            $existing = $this->findByHash($cacheKey);

            if ($existing === null) {
                throw new LogicException('Cache row disappeared after unique constraint violation');
            }

            return $existing;
        }
        //DB failure fallback to LocalPacker ?
    }
}
