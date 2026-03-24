<?php

declare(strict_types=1);

use App\Application;
use DI\ContainerBuilder;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;

require __DIR__ . '/vendor/autoload.php';

$builder = new ContainerBuilder();
$builder->addDefinitions(__DIR__ . '/config/container.php');
$container = $builder->build();

$request = new Request('POST', new Uri('http://localhost/pack'), ['Content-Type' => 'application/json'], $argv[1]);

$application = $container->get(Application::class);
$response = $application->run($request);

echo "<<< In:\n" . Message::toString($request) . "\n\n";
echo ">>> Out:\n" . Message::toString($response) . "\n\n";
