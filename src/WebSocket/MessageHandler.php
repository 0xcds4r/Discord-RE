<?php

namespace Messenger\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Messenger\Service\AuthService;
use Messenger\Service\MessageService;
use Messenger\Service\ChannelService;
use Messenger\Entity\User;
use Doctrine\ORM\EntityManager;

class MessageHandler implements MessageComponentInterface
{
    private \SplObjectStorage $clients;
    private array $userConnections;
    private AuthService $authService;
    private MessageService $messageService;
    private ChannelService $channelService;
    private EntityManager $entityManager;

    public function __construct(
        AuthService $authService,
        MessageService $messageService,
        ChannelService $channelService,
        EntityManager $entityManager
    ) {
        $this->clients = new \SplObjectStorage();
        $this->userConnections = [];
        $this->authService = $authService;
        $this->messageService = $messageService;
        $this->channelService = $channelService;
        $this->entityManager = $entityManager;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        
        if (!$data || !isset($data['type'])) {
            $this->sendError($from, 'Invalid message format');
            return;
        }

        switch ($data['type']) {
            case 'auth':
                $this->handleAuth($from, $data);
                break;
            case 'message':
                $this->handleMessage($from, $data);
                break;
            default:
                $this->sendError($from, 'Unknown message type');
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        
        // Remove user connection mapping
        foreach ($this->userConnections as $userId => $connection) {
            if ($connection === $conn) {
                unset($this->userConnections[$userId]);
                echo "User {$userId} disconnected ({$conn->resourceId})\n";
                break;
            }
        }
        
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    private function handleAuth(ConnectionInterface $conn, array $data)
    {
        if (!isset($data['token'])) {
            $this->sendAuthStatus($conn, false, 'Token required');
            return;
        }

        $user = $this->authService->validateToken($data['token']);
        if (!$user) {
            $this->sendAuthStatus($conn, false, 'Invalid token');
            return;
        }

        $this->userConnections[$user->getId()] = $conn;
        $conn->user = $user;

        $user->setLastActiveAt(new \DateTime());
        $this->entityManager->flush();

        $this->sendAuthStatus($conn, true, 'Authentication successful');
        $this->broadcastUserStatus($user->getId(), 'online');
        
        echo "User {$user->getUsername()} authenticated ({$conn->resourceId})\n";
    }

    private function handleMessage(ConnectionInterface $from, array $data)
    {
        if (!isset($from->user)) {
            $this->sendError($from, 'Not authenticated');
            return;
        }

        if (!isset($data['content']) || empty(trim($data['content']))) {
            $this->sendError($from, 'Message content required');
            return;
        }

        $sender = $from->user;
        $content = trim($data['content']);

        try {
            if (isset($data['channel_id']) && $data['channel_id']) 
            {
                $channel = $this->channelService->getChannelById($data['channel_id']);
                if (!$channel) {
                    $this->sendError($from, 'Channel not found');
                    return;
                }

                if (!$channel->getMembers()->contains($sender)) {
                    $this->sendError($from, 'You are not a member of this channel');
                    return;
                }

                $message = $this->messageService->sendChannelMessage($sender, $channel, $content);
                $this->broadcastChannelMessage($message, $channel);

            } 
            elseif (isset($data['receiver_id']) && $data['receiver_id']) 
            {
                $receiver = $this->entityManager->find(User::class, $data['receiver_id']);
                if (!$receiver) {
                    $this->sendError($from, 'Receiver not found');
                    return;
                }

                $message = $this->messageService->sendDirectMessage($sender, $receiver, $content);
                $this->broadcastDirectMessage($message);
            } 
            else 
            {
                $this->sendError($from, 'Either channel_id or receiver_id must be specified');
            }
        } catch (\Exception $e) {
            $this->sendError($from, 'Failed to send message: ' . $e->getMessage());
        }
    }

    private function broadcastChannelMessage($message, $channel)
    {
        $messageData = [
            'type' => 'new_message',
            'message_id' => $message->getId(),
            'sender_id' => $message->getSender()->getId(),
            'sender_username' => $message->getSender()->getUsername(),
            'channel_id' => $channel->getId(),
            'receiver_id' => null,
            'content' => $message->getContent(),
            'sent_at' => $message->getSentAt()->format('Y-m-d H:i:s')
        ];

        foreach ($channel->getMembers() as $member) {
            if (isset($this->userConnections[$member->getId()])) {
                $this->userConnections[$member->getId()]->send(json_encode($messageData));
            }
        }
    }

    private function broadcastDirectMessage($message)
    {
        $messageData = [
            'type' => 'new_message',
            'message_id' => $message->getId(),
            'sender_id' => $message->getSender()->getId(),
            'sender_username' => $message->getSender()->getUsername(),
            'channel_id' => null,
            'receiver_id' => $message->getReceiver()->getId(),
            'content' => $message->getContent(),
            'sent_at' => $message->getSentAt()->format('Y-m-d H:i:s')
        ];

        $senderId = $message->getSender()->getId();
        $receiverId = $message->getReceiver()->getId();

        if (isset($this->userConnections[$senderId])) {
            $this->userConnections[$senderId]->send(json_encode($messageData));
        }

        if (isset($this->userConnections[$receiverId])) {
            $this->userConnections[$receiverId]->send(json_encode($messageData));
        }
    }

    private function broadcastUserStatus($userId, $status)
    {
        $statusData = [
            'type' => 'user_status_update',
            'user_id' => $userId,
            'status' => $status
        ];

        foreach ($this->clients as $client) {
            if (isset($client->user)) {
                $client->send(json_encode($statusData));
            }
        }
    }

    private function sendAuthStatus(ConnectionInterface $conn, bool $success, string $message)
    {
        $response = [
            'type' => 'auth_status',
            'success' => $success,
            'message' => $message
        ];
        $conn->send(json_encode($response));
    }

    private function sendError(ConnectionInterface $conn, string $message)
    {
        $response = [
            'type' => 'error',
            'message' => $message
        ];
        $conn->send(json_encode($response));
    }
}

