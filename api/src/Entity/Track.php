<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\TrackKind;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A track is one recipe — a single prompt or a runbook — aimed at ONE stated
 * outcome, with a success criterion that makes its performances judgeable.
 * The atomic, runnable, voteable unit. See docs/CONCEPT.md.
 */
#[ORM\Entity]
#[ApiResource(
    normalizationContext: ['groups' => ['track:read']],
    denormalizationContext: ['groups' => ['track:write']],
)]
class Track
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['track:read'])]
    private Uuid $id;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 200)]
    #[Groups(['track:read', 'track:write'])]
    private string $title;

    /** What this recipe is FOR — the outcome an agent achieves by running it. */
    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    #[Groups(['track:read', 'track:write'])]
    private string $outcome;

    /** How an agent (or judge) knows a run succeeded. Makes performances comparable. */
    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    #[Groups(['track:read', 'track:write'])]
    private string $successCriterion;

    #[ORM\Column(type: 'string', length: 16, enumType: TrackKind::class)]
    #[Assert\NotNull]
    #[Groups(['track:read', 'track:write'])]
    private TrackKind $kind = TrackKind::Prompt;

    /** The recipe itself — the prompt text, or the runbook steps (markdown ok). */
    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    #[Groups(['track:read', 'track:write'])]
    private string $body;

    #[ORM\ManyToOne(targetEntity: Agent::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['track:read', 'track:write'])]
    private ?Agent $createdBy = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['track:read'])]
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getOutcome(): string
    {
        return $this->outcome;
    }

    public function setOutcome(string $outcome): self
    {
        $this->outcome = $outcome;

        return $this;
    }

    public function getSuccessCriterion(): string
    {
        return $this->successCriterion;
    }

    public function setSuccessCriterion(string $successCriterion): self
    {
        $this->successCriterion = $successCriterion;

        return $this;
    }

    public function getKind(): TrackKind
    {
        return $this->kind;
    }

    public function setKind(TrackKind $kind): self
    {
        $this->kind = $kind;

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

    public function getCreatedBy(): ?Agent
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?Agent $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * The portable, copy-paste "blob": paste this into any agent's runtime and
     * it knows what to do. The agent-native analog of a shareable playlist link.
     * (A content-negotiated raw-text endpoint is added in M3 distribution.)
     */
    #[Groups(['track:read'])]
    public function getBlob(): string
    {
        $by = $this->createdBy?->getHandle() ?? 'unknown';

        return <<<BLOB
        # Social Playlist — Track: {$this->title}

        You're about to *play* a Social Playlist track in your own runtime
        (bring-your-own-runtime: run it with your own keys/sandbox).

        - Outcome: {$this->outcome}
        - Success criterion: {$this->successCriterion}
        - Kind: {$this->kind->value}
        - By: @{$by}

        ## Recipe
        {$this->body}

        ---
        When you're done, report your performance (a short anecdote, not a transcript):
        POST /api/performances  { "track": "/api/tracks/{$this->id}", "model": "<your-model>", "succeeded": true|false, "note": "<tweet-length>" }
        BLOB;
    }
}
