<?php

namespace Messenger\Service;

use Messenger\Entity\User;
use Messenger\Entity\Channel;
use Doctrine\ORM\EntityManager;

class ChannelService
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function createChannel(string $name, string $description, bool $isPrivate, User $creator): array
    {
        $existingChannel = $this->entityManager->getRepository(Channel::class)->findOneBy(['name' => $name]);
        if ($existingChannel) {
            return ['success' => false, 'message' => 'Channel name already exists'];
        }

        $channel = new Channel();
        $channel->setName($name);
        $channel->setDescription($description);
        $channel->setIsPrivate($isPrivate);
        $channel->setCreatedBy($creator);
        $channel->addMember($creator);

        $this->entityManager->persist($channel);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => 'Channel created successfully',
            'channel_id' => $channel->getId()
        ];
    }

    public function getChannels(): array
    {
        $channels = $this->entityManager->getRepository(Channel::class)->findBy(['isPrivate' => false]);
        
        return array_map(function(Channel $channel) {
            return [
                'id' => $channel->getId(),
                'name' => $channel->getName(),
                'description' => $channel->getDescription(),
                'is_private' => $channel->isPrivate(),
                'member_count' => $channel->getMembers()->count()
            ];
        }, $channels);
    }

    public function joinChannel(Channel $channel, User $user): array
    {
        if ($channel->getMembers()->contains($user)) {
            return ['success' => false, 'message' => 'User is already a member of this channel'];
        }

        $channel->addMember($user);
        $this->entityManager->flush();

        return ['success' => true, 'message' => 'Successfully joined the channel'];
    }

    public function leaveChannel(Channel $channel, User $user): array
    {
        if (!$channel->getMembers()->contains($user)) {
            return ['success' => false, 'message' => 'User is not a member of this channel'];
        }

        $channel->removeMember($user);
        $this->entityManager->flush();

        return ['success' => true, 'message' => 'Successfully left the channel'];
    }

    public function getChannelById(int $id): ?Channel
    {
        return $this->entityManager->find(Channel::class, $id);
    }

    public function getUserChannels(User $user): array
    {
        $channels = $user->getChannels();
        
        return array_map(function(Channel $channel) {
            return [
                'id' => $channel->getId(),
                'name' => $channel->getName(),
                'description' => $channel->getDescription(),
                'is_private' => $channel->isPrivate(),
                'member_count' => $channel->getMembers()->count()
            ];
        }, $channels->toArray());
    }
}

