<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Messenger\WebSocket\MessageHandler;
use Messenger\Service\AuthService;
use Messenger\Service\MessageService;
use Messenger\Service\ChannelService;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$entityManager = require __DIR__ . '/../config/doctrine.php';

$authService = new AuthService($entityManager, $_ENV['JWT_SECRET'], $_ENV['JWT_ALGORITHM']);
$messageService = new MessageService($entityManager);
$channelService = new ChannelService($entityManager);

$messageHandler = new MessageHandler($authService, $messageService, $channelService, $entityManager);

$server = IoServer::factory(
    new HttpServer(
        new WsServer($messageHandler)
    ),
    $_ENV['WEBSOCKET_PORT'] ?? 8081,
    $_ENV['WEBSOCKET_HOST'] ?? '0.0.0.0'
);

echo "WebSocket server started on {$_ENV['WEBSOCKET_HOST']}:{$_ENV['WEBSOCKET_PORT']}\n";
echo "Press Ctrl+C to stop the server\n";

$server->run();

