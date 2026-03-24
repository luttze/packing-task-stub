<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;

require __DIR__ . '/vendor/autoload.php';

$builder = new ContainerBuilder();
$builder->addDefinitions(__DIR__ . '/config/container.php');
$container = $builder->build();

$entityManager = $container->get(EntityManager::class);

return ConsoleRunner::createHelperSet($entityManager); // needed by vendor/bin/doctrine
