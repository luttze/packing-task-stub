<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Dto\ProductInput;
use App\Entity\Packaging;
use App\Service\InputValidator;
use DI\Container;
use DI\ContainerBuilder;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use PHPUnit\Framework\TestCase;

/**
 * Base class for integration tests.
 *
 * Provides a real DI container with:
 * - SQLite in-memory database (auto-created schema)
 * - Seeded packaging boxes matching production data
 * - Guzzle MockHandler for stubbing API responses
 */
abstract class IntegrationTestCase extends TestCase
{
    protected Container $container;
    protected EntityManager $entityManager;
    protected MockHandler $mockHttpHandler;

    protected function setUp(): void
    {
        $this->mockHttpHandler = new MockHandler();

        $builder = new ContainerBuilder();
        $builder->addDefinitions(__DIR__ . '/../../config/container.php');
        $builder->addDefinitions($this->overrideDefinitions());
        $this->container = $builder->build();

        $this->entityManager = $this->container->get(EntityManager::class);
        $this->createSchema();
        $this->seedBoxes();
    }

    /**
     * @return array<string, mixed>
     */
    private function overrideDefinitions(): array
    {
        $mockHandler = $this->mockHttpHandler;

        return [
            EntityManager::class => static function (): EntityManager {
                $config = ORMSetup::createAttributeMetadataConfiguration(
                    [__DIR__ . '/../../src'],
                    true,
                );
                $config->setNamingStrategy(new UnderscoreNamingStrategy());

                $connection = DriverManager::getConnection([
                    'driver' => 'pdo_sqlite',
                    'memory' => true,
                ]);

                return new EntityManager($connection, $config);
            },

            ClientInterface::class => static function () use ($mockHandler): ClientInterface {
                $stack = HandlerStack::create($mockHandler);

                return new Client(['handler' => $stack]);
            },
        ];
    }

    private function createSchema(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);
    }

    private function seedBoxes(): void
    {
        $boxes = [
            new Packaging(2.5, 3.0, 1.0, 20),
            new Packaging(4.0, 4.0, 4.0, 20),
            new Packaging(2.0, 2.0, 10.0, 20),
            new Packaging(5.5, 6.0, 7.5, 30),
            new Packaging(9.0, 9.0, 9.0, 30),
        ];

        foreach ($boxes as $box) {
            $this->entityManager->persist($box);
        }

        $this->entityManager->flush();
    }

    protected function buildRequestBody(array $products): string
    {
        return json_encode(['products' => $products], JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<array<string, mixed>> $rawProducts
     * @return ProductInput[]
     */
    protected function validateProducts(array $rawProducts): array
    {
        return $this->container->get(InputValidator::class)->validateAndMapProducts($rawProducts);
    }
}
