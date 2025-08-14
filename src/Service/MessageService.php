<?php

namespace Messenger\Service;

use Messenger\Entity\User;
use Messenger\Entity\Channel;
use Messenger\Entity\Message;
use Doctrine\ORM\EntityManager;

class MessageService
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function sendChannelMessage(User $sender, Channel $channel, string $content): Message
    {
        $message = new Message();
        $message->setSender($sender);
        $message->setChannel($channel);
        $message->setContent($content);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return $message;
    }

    public function sendDirectMessage(User $sender, User $receiver, string $content): Message
    {
        $message = new Message();
        $message->setSender($sender);
        $message->setReceiver($receiver);
        $message->setContent($content);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return $message;
    }

    public function getChannelMessages(Channel $channel, int $limit = 50, int $offset = 0): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('m', 's')
           ->from(Message::class, 'm')
           ->join('m.sender', 's')
           ->where('m.channel = :channel')
           ->setParameter('channel', $channel)
           ->orderBy('m.sentAt', 'DESC')
           ->setMaxResults($limit)
           ->setFirstResult($offset);

        $messages = $qb->getQuery()->getResult();
        
        return array_map([$this, 'formatMessage'], array_reverse($messages));
    }

    public function getDirectMessages(User $user1, User $user2, int $limit = 50, int $offset = 0): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('m', 's')
           ->from(Message::class, 'm')
           ->join('m.sender', 's')
           ->where('(m.sender = :user1 AND m.receiver = :user2) OR (m.sender = :user2 AND m.receiver = :user1)')
           ->setParameter('user1', $user1)
           ->setParameter('user2', $user2)
           ->orderBy('m.sentAt', 'DESC')
           ->setMaxResults($limit)
           ->setFirstResult($offset);

        $messages = $qb->getQuery()->getResult();
        
        return array_map([$this, 'formatMessage'], array_reverse($messages));
    }

    public function formatMessage(Message $message): array
    {
        return [
            'id' => $message->getId(),
            'sender_id' => $message->getSender()->getId(),
            'sender_username' => $message->getSender()->getUsername(),
            'channel_id' => $message->getChannel() ? $message->getChannel()->getId() : null,
            'receiver_id' => $message->getReceiver() ? $message->getReceiver()->getId() : null,
            'content' => $message->getContent(),
            'sent_at' => $message->getSentAt()->format('Y-m-d H:i:s')
        ];
    }
}

