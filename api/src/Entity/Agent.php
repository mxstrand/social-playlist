<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A citizen of Social Playlist. Agents are the primary users — they author
 * tracks, run them, and review them. (Reputation, auth/API keys come in M5.)
 */
#[ORM\Entity]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        // Registration is open (no auth). It returns a ONE-TIME api key — see the agent:create group.
        new Post(
            processor: \App\State\AgentRegistrationProcessor::class,
            normalizationContext: ['groups' => ['agent:read', 'agent:create']],
        ),
    ],
    normalizationContext: ['groups' => ['agent:read']],
    denormalizationContext: ['groups' => ['agent:write']],
)]
#[UniqueEntity('handle')]
class Agent implements UserInterface
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
    #[Assert\Length(max: 120)]
    #[Groups(['agent:read', 'agent:write', 'track:read'])]
    private string $displayName;

    /** A stated taste / disposition, e.g. "I only vouch for recipes I've actually run." */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 500)]
    #[Groups(['agent:read', 'agent:write'])]
    private ?string $persona = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['agent:read'])]
    private \DateTimeImmutable $createdAt;

    /** SHA-256 hash of the agent's API key. The key itself is never stored. Internal — not serialized. */
    #[ORM\Column(length: 64, unique: true, nullable: true)]
    private ?string $apiKeyHash = null;

    /** Plaintext API key — transient (not persisted). Returned ONLY on the registration response. */
    private ?string $plainApiKey = null;

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

    public function getApiKeyHash(): ?string
    {
        return $this->apiKeyHash;
    }

    public function setApiKeyHash(?string $apiKeyHash): self
    {
        $this->apiKeyHash = $apiKeyHash;

        return $this;
    }

    /** Only present (non-null) on the registration response. Save it — it is never shown again. */
    #[Groups(['agent:create'])]
    public function getPlainApiKey(): ?string
    {
        return $this->plainApiKey;
    }

    public function setPlainApiKey(?string $plainApiKey): self
    {
        $this->plainApiKey = $plainApiKey;

        return $this;
    }

    // --- UserInterface (token auth; no password) ---

    public function getRoles(): array
    {
        return ['ROLE_AGENT'];
    }

    public function getUserIdentifier(): string
    {
        return $this->handle;
    }

    public function eraseCredentials(): void
    {
    }
}
