<?php

namespace Messenger\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Messenger\Service\ChannelService;
use Messenger\Service\MessageService;
use Messenger\Entity\User;

class ChannelController
{
    private ChannelService $channelService;
    private MessageService $messageService;

    public function __construct(ChannelService $channelService, MessageService $messageService)
    {
        $this->channelService = $channelService;
        $this->messageService = $messageService;
    }

    public function createChannel(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $user = $request->getAttribute('user');

        if (!isset($data['name'])) {
            $result = ['success' => false, 'message' => 'Channel name is required'];
        } else {
            $result = $this->channelService->createChannel(
                $data['name'],
                $data['description'] ?? '',
                $data['is_private'] ?? false,
                $user
            );
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getChannels(Request $request, Response $response): Response
    {
        $channels = $this->channelService->getChannels();
        
        $response->getBody()->write(json_encode($channels));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function joinChannel(Request $request, Response $response): Response
    {
        $channelId = $request->getAttribute('id');
        $user = $request->getAttribute('user');
        
        $channel = $this->channelService->getChannelById($channelId);
        if (!$channel) {
            $result = ['success' => false, 'message' => 'Channel not found'];
        } else {
            $result = $this->channelService->joinChannel($channel, $user);
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function leaveChannel(Request $request, Response $response): Response
    {
        $channelId = $request->getAttribute('id');
        $user = $request->getAttribute('user');
        
        $channel = $this->channelService->getChannelById($channelId);
        if (!$channel) {
            $result = ['success' => false, 'message' => 'Channel not found'];
        } else {
            $result = $this->channelService->leaveChannel($channel, $user);
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getChannelMessages(Request $request, Response $response): Response
    {
        $channelId = $request->getAttribute('id');
        
        $channel = $this->channelService->getChannelById($channelId);
        if (!$channel) {
            $result = ['error' => 'Channel not found'];
        } else {
            $result = $this->messageService->getChannelMessages($channel);
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

