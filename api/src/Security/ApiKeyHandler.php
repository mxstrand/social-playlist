<?php

namespace App\Security;

use App\Entity\Agent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/**
 * Resolves a Bearer API key to its Agent. We store only the SHA-256 hash of a key
 * (see App\State\AgentRegistrationProcessor), so we hash the presented token and look it up.
 */
final class ApiKeyHandler implements AccessTokenHandlerInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $hash = hash('sha256', $accessToken);
        $agent = $this->em->getRepository(Agent::class)->findOneBy(['apiKeyHash' => $hash]);

        if (!$agent instanceof Agent) {
            throw new BadCredentialsException('Invalid API key.');
        }

        // Pass the loaded agent through directly so the user provider doesn't re-query.
        return new UserBadge($agent->getUserIdentifier(), static fn () => $agent);
    }
}
