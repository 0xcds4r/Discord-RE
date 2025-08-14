<?php

namespace Messenger\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $username;

    #[ORM\Column(type: 'string', length: 255)]
    private string $passwordHash;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $lastActiveAt;

    #[ORM\ManyToMany(targetEntity: Channel::class, mappedBy: 'members')]
    private Collection $channels;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'sender')]
    private Collection $sentMessages;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'receiver')]
    private Collection $receivedMessages;

    public function __construct()
    {
        $this->channels = new ArrayCollection();
        $this->sentMessages = new ArrayCollection();
        $this->receivedMessages = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->lastActiveAt = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getLastActiveAt(): \DateTime
    {
        return $this->lastActiveAt;
    }

    public function setLastActiveAt(\DateTime $lastActiveAt): self
    {
        $this->lastActiveAt = $lastActiveAt;
        return $this;
    }

    public function getChannels(): Collection
    {
        return $this->channels;
    }

    public function addChannel(Channel $channel): self
    {
        if (!$this->channels->contains($channel)) {
            $this->channels[] = $channel;
            $channel->addMember($this);
        }
        return $this;
    }

    public function removeChannel(Channel $channel): self
    {
        if ($this->channels->removeElement($channel)) {
            $channel->removeMember($this);
        }
        return $this;
    }

    public function getSentMessages(): Collection
    {
        return $this->sentMessages;
    }

    public function getReceivedMessages(): Collection
    {
        return $this->receivedMessages;
    }
}

