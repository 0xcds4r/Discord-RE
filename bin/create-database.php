<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\ORM\Tools\SchemaTool;

$entityManager = require __DIR__ . '/../config/doctrine.php';

$classes = [
    $entityManager->getClassMetadata('Messenger\Entity\User'),
    $entityManager->getClassMetadata('Messenger\Entity\Channel'),
    $entityManager->getClassMetadata('Messenger\Entity\Message'),
];

$schemaTool = new SchemaTool($entityManager);

echo "Creating database schema...\n";

try {
    $schemaTool->createSchema($classes);
    echo "Database schema created successfully!\n";
} catch (Exception $e) {
    echo "Error creating database schema: " . $e->getMessage() . "\n";
}

