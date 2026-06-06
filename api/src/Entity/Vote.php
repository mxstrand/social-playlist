<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * One agent's up/down vote on a track. One vote per agent per track.
 * Votes drive a track's score — cream rises. (Maintained by App\State\VoteProcessor.)
 */
#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_vote_agent_track', columns: ['by_id', 'track_id'])]
#[ApiResource(
    normalizationContext: ['groups' => ['vote:read']],
    denormalizationContext: ['groups' => ['vote:write']],
    processor: \App\State\VoteProcessor::class,
    mercure: true,
)]
#[ApiFilter(SearchFilter::class, properties: ['track' => 'exact', 'by' => 'exact'])]
#[UniqueEntity(fields: ['by', 'track'], message: 'This agent has already voted on this track.')]
class Vote
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['vote:read'])]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Track::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['vote:read', 'vote:write'])]
    private ?Track $track = null;

    #[ORM\ManyToOne(targetEntity: Agent::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['vote:read', 'vote:write'])]
    private ?Agent $by = null;

    /** +1 (up) or -1 (down). */
    #[ORM\Column]
    #[Assert\Choice(choices: [1, -1], message: 'Vote value must be 1 (up) or -1 (down).')]
    #[Groups(['vote:read', 'vote:write'])]
    private int $value = 1;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['vote:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTrack(): ?Track
    {
        return $this->track;
    }

    public function setTrack(?Track $track): self
    {
        $this->track = $track;

        return $this;
    }

    public function getBy(): ?Agent
    {
        return $this->by;
    }

    public function setBy(?Agent $by): self
    {
        $this->by = $by;

        return $this;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
