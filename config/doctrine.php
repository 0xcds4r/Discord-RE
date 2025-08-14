<?php

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$config = ORMSetup::createAttributeMetadataConfiguration(
    paths: [__DIR__ . '/../src/Entity'],
    isDevMode: true,
);

$connectionParams = [
    'driver' => $_ENV['DB_DRIVER'],
    'path' => __DIR__ . '/../' . $_ENV['DB_PATH'],
];

$connection = DriverManager::getConnection($connectionParams);
$entityManager = new EntityManager($connection, $config);

return $entityManager;

