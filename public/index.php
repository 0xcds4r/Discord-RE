<?php

use Slim\Factory\AppFactory;
use Slim\Middleware\BodyParsingMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use Messenger\Controller\AuthController;
use Messenger\Controller\ChannelController;
use Messenger\Controller\UserController;
use Messenger\Service\AuthService;
use Messenger\Service\ChannelService;
use Messenger\Service\MessageService;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$entityManager = require __DIR__ . '/../config/doctrine.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

$app->add(function (Request $request, RequestHandler $handler): Response {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$app->options('/{routes:.+}', function (Request $request, Response $response) {
    return $response;
});

$authService = new AuthService($entityManager, $_ENV['JWT_SECRET'], $_ENV['JWT_ALGORITHM']);
$channelService = new ChannelService($entityManager);
$messageService = new MessageService($entityManager);

$authController = new AuthController($authService);
$channelController = new ChannelController($channelService, $messageService);
$userController = new UserController($entityManager, $messageService, $channelService);

$authMiddleware = function (Request $request, RequestHandler $handler) use ($authService): Response {
    $authHeader = $request->getHeaderLine('Authorization');
    
    if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Authorization token required']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    $token = $matches[1];
    $user = $authService->validateToken($token);
    
    if (!$user) {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Invalid token']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    $request = $request->withAttribute('user', $user);
    return $handler->handle($request);
};

$app->post('/api/register', [$authController, 'register']);
$app->post('/api/login', [$authController, 'login']);

$app->group('/api', function ($group) use ($channelController, $userController) 
{
    $group->get('/channels', [$channelController, 'getChannels']);
    $group->post('/channels', [$channelController, 'createChannel']);
    $group->post('/channels/{id}/join', [$channelController, 'joinChannel']);
    $group->post('/channels/{id}/leave', [$channelController, 'leaveChannel']);
    $group->get('/channels/{id}/messages', [$channelController, 'getChannelMessages']);
    
    $group->get('/user/channels', [$userController, 'getUserChannels']);
    $group->get('/user/{id}', [$userController, 'getUser']);
    $group->get('/user/{id}/messages', [$userController, 'getDirectMessages']);
})->add($authMiddleware);

// debug
$app->get('/api/health', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode(['status' => 'ok', 'timestamp' => date('Y-m-d H:i:s')]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();

