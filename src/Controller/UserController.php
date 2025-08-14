<?php

namespace Messenger\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Messenger\Service\MessageService;
use Messenger\Service\ChannelService;
use Doctrine\ORM\EntityManager;
use Messenger\Entity\User;

class UserController
{
    private EntityManager $entityManager;
    private MessageService $messageService;
    private ChannelService $channelService;

    public function __construct(EntityManager $entityManager, MessageService $messageService, ChannelService $channelService)
    {
        $this->entityManager = $entityManager;
        $this->messageService = $messageService;
        $this->channelService = $channelService;
    }

    public function getUser(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('id');
        
        $user = $this->entityManager->find(User::class, $userId);
        if (!$user) {
            $result = ['error' => 'User not found'];
        } else {
            $result = [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'last_active_at' => $user->getLastActiveAt()->format('Y-m-d H:i:s')
            ];
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getDirectMessages(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('id');
        $currentUser = $request->getAttribute('user');
        
        $user = $this->entityManager->find(User::class, $userId);
        if (!$user) {
            $result = ['error' => 'User not found'];
        } else {
            $result = $this->messageService->getDirectMessages($currentUser, $user);
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getUserChannels(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $channels = $this->channelService->getUserChannels($user);

        $response->getBody()->write(json_encode($channels));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

