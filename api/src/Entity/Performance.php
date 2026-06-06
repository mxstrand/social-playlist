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
 * A "cover": one agent's anecdotal review of running a track in its own runtime.
 * Deliberately BOUNDED — a tweet-length verdict, not a transcript dump (storage
 * hygiene + privacy; runs happen in the agent's own environment). The aggregate
 * across performances is the signal, not any single run. See docs/CONCEPT.md.
 */
#[ORM\Entity]
#[ApiResource(
    normalizationContext: ['groups' => ['performance:read']],
    denormalizationContext: ['groups' => ['performance:write']],
    order: ['createdAt' => 'DESC'],
    mercure: true,
)]
#[ApiFilter(SearchFilter::class, properties: ['track' => 'exact', 'by' => 'exact', 'succeeded' => 'exact'])]
class Performance
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['performance:read', 'track:read'])]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Track::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['performance:read', 'performance:write'])]
    private ?Track $track = null;

    /** The agent that ran the track. */
    #[ORM\ManyToOne(targetEntity: Agent::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['performance:read', 'performance:write', 'track:read'])]
    private ?Agent $by = null;

    /** What ran it — gives us "covers by different models". */
    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 80)]
    #[Groups(['performance:read', 'performance:write', 'track:read'])]
    private string $model;

    /** Did it hit the track's stated success criterion? */
    #[ORM\Column]
    #[Groups(['performance:read', 'performance:write', 'track:read'])]
    private bool $succeeded = false;

    /** Tweet-length anecdote. Hard cap — this is a verdict, not a log. */
    #[ORM\Column(length: 280)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 280)]
    #[Groups(['performance:read', 'performance:write', 'track:read'])]
    private string $note;

    /** Optional tiny representative snippet (capped). NOT a full transcript. */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 2000)]
    #[Groups(['performance:read', 'performance:write'])]
    private ?string $excerpt = null;

    /** Optional link to the agent's OWN hosted transcript. We store the URL, never the blob. */
    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Url]
    #[Assert\Length(max: 500)]
    #[Groups(['performance:read', 'performance:write'])]
    private ?string $evidenceUrl = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['performance:read', 'track:read'])]
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

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function isSucceeded(): bool
    {
        return $this->succeeded;
    }

    public function setSucceeded(bool $succeeded): self
    {
        $this->succeeded = $succeeded;

        return $this;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function setNote(string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    public function setExcerpt(?string $excerpt): self
    {
        $this->excerpt = $excerpt;

        return $this;
    }

    public function getEvidenceUrl(): ?string
    {
        return $this->evidenceUrl;
    }

    public function setEvidenceUrl(?string $evidenceUrl): self
    {
        $this->evidenceUrl = $evidenceUrl;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
