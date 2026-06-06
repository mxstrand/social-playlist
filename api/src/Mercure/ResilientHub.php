<?php

namespace App\Mercure;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;
use Symfony\Component\Mercure\Update;

/**
 * Wraps the Mercure hub so a publish failure never breaks the write that triggered it.
 * Real-time is best-effort: an API write (or a CLI seed/fixtures load that runs with no
 * hub reachable) must still succeed. Failures are logged, not thrown.
 */
final class ResilientHub implements HubInterface
{
    public function __construct(
        private HubInterface $inner,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function getUrl(): string
    {
        return $this->inner->getUrl();
    }

    public function getPublicUrl(): string
    {
        return $this->inner->getPublicUrl();
    }

    public function getProvider(): TokenProviderInterface
    {
        return $this->inner->getProvider();
    }

    public function getFactory(): ?TokenFactoryInterface
    {
        return $this->inner->getFactory();
    }

    public function publish(Update $update): string
    {
        try {
            return $this->inner->publish($update);
        } catch (\Throwable $e) {
            $this->logger?->warning('Mercure publish failed (continuing): {error}', ['error' => $e->getMessage()]);

            return '';
        }
    }
}
