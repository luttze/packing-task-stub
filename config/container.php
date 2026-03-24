<?php

declare(strict_types=1);

use App\Repository\DoctrinePackagingRepository;
use App\Repository\DoctrinePackingCacheRepository;
use App\Repository\PackagingRepositoryInterface;
use App\Repository\PackingCacheRepositoryInterface;
use App\Service\Packer\BinPackingApiPacker;
use App\Service\Packer\FallbackPacker;
use App\Service\Packer\LocalPacker;
use App\Service\Packer\PackerInterface;

use function DI\autowire;
use function DI\create;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use GuzzleHttp\Client;

use GuzzleHttp\ClientInterface;
use Psr\Container\ContainerInterface;

return [
    // Doctrine
    EntityManager::class => static function (): EntityManager {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/../src'], true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy());

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_mysql',
            'host' => getenv('DB_HOST') ?: 'shipmonk-packing-mysql',
            'user' => getenv('DB_USER') ?: 'root',
            'password' => getenv('DB_PASSWORD') ?: 'secret',
            'dbname' => getenv('DB_NAME') ?: 'packing',
        ]);

        return new EntityManager($connection, $config);
    },

    // Repositories
    PackagingRepositoryInterface::class => autowire(DoctrinePackagingRepository::class),
    PackingCacheRepositoryInterface::class => autowire(DoctrinePackingCacheRepository::class),

    // HTTP client
    ClientInterface::class => create(Client::class),

    // Packer chain: API -> fallback to local
    BinPackingApiPacker::class => autowire(BinPackingApiPacker::class),

    PackerInterface::class => static function (ContainerInterface $c): PackerInterface {
        return new FallbackPacker(
            $c->get(BinPackingApiPacker::class),
            $c->get(LocalPacker::class),
        );
    },
];
