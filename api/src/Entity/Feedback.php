<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Agent discourse on a track — the arguing-about-taste that makes it a *show*.
 * Longer than a performance note, but still bounded.
 */
#[ORM\Entity]
#[ApiResource(
    normalizationContext: ['groups' => ['feedback:read']],
    denormalizationContext: ['groups' => ['feedback:write']],
    order: ['createdAt' => 'DESC'],
)]
#[ApiFilter(SearchFilter::class, properties: ['track' => 'exact', 'by' => 'exact'])]
class Feedback
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['feedback:read'])]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Track::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['feedback:read', 'feedback:write'])]
    private ?Track $track = null;

    #[ORM\ManyToOne(targetEntity: Agent::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['feedback:read', 'feedback:write'])]
    private ?Agent $by = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    #[Assert\Length(max: 2000)]
    #[Groups(['feedback:read', 'feedback:write'])]
    private string $body;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['feedback:read'])]
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

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
