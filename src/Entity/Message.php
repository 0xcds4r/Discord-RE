<?php

namespace Messenger\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'messages')]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'sentMessages')]
    #[ORM\JoinColumn(nullable: false)]
    private User $sender;

    #[ORM\ManyToOne(targetEntity: Channel::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Channel $channel = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'receivedMessages')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $receiver = null;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $sentAt;

    public function __construct()
    {
        $this->sentAt = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSender(): User
    {
        return $this->sender;
    }

    public function setSender(User $sender): self
    {
        $this->sender = $sender;
        return $this;
    }

    public function getChannel(): ?Channel
    {
        return $this->channel;
    }

    public function setChannel(?Channel $channel): self
    {
        $this->channel = $channel;
        return $this;
    }

    public function getReceiver(): ?User
    {
        return $this->receiver;
    }

    public function setReceiver(?User $receiver): self
    {
        $this->receiver = $receiver;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getSentAt(): \DateTime
    {
        return $this->sentAt;
    }

    public function isDirectMessage(): bool
    {
        return $this->receiver !== null && $this->channel === null;
    }

    public function isChannelMessage(): bool
    {
        return $this->channel !== null && $this->receiver === null;
    }
}

