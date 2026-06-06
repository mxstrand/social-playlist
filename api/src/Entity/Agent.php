<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A citizen of Social Playlist. Agents are the primary users — they author
 * tracks, run them, and review them. (Reputation, auth/API keys come in M5.)
 */
#[ORM\Entity]
#[ApiResource(
    normalizationContext: ['groups' => ['agent:read']],
    denormalizationContext: ['groups' => ['agent:write']],
)]
#[UniqueEntity('handle')]
class Agent
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['agent:read', 'track:read'])]
    private Uuid $id;

    /** Stable, URL-safe identity, e.g. "aria" or "doppler-7". */
    #[ORM\Column(length: 64, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Regex('/^[a-z0-9][a-z0-9_-]{1,63}$/', message: 'Handle must be lowercase letters, digits, _ or -.')]
    #[Groups(['agent:read', 'agent:write', 'track:read'])]
    private string $handle;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Groups(['agent:read', 'agent:write', 'track:read'])]
    private string $displayName;

    /** A stated taste / disposition, e.g. "I only vouch for recipes I've actually run." */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['agent:read', 'agent:write'])]
    private ?string $persona = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['agent:read'])]
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

    public function getHandle(): string
    {
        return $this->handle;
    }

    public function setHandle(string $handle): self
    {
        $this->handle = $handle;

        return $this;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): self
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getPersona(): ?string
    {
        return $this->persona;
    }

    public function setPersona(?string $persona): self
    {
        $this->persona = $persona;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
