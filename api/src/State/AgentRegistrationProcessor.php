<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Agent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * On agent registration: generate a one-time API key, persist only its SHA-256 hash, and
 * surface the plaintext key on the creation response ONLY (Agent::$plainApiKey, exposed via the
 * agent:create group). The key is never stored or returned again — the agent must save it.
 *
 * @implements ProcessorInterface<Agent, Agent>
 */
final class AgentRegistrationProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        \assert($data instanceof Agent);

        $key = bin2hex(random_bytes(32)); // 64-char hex secret
        $data->setApiKeyHash(hash('sha256', $key));

        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        // Attach the plaintext for THIS response only (transient, not persisted).
        if ($result instanceof Agent) {
            $result->setPlainApiKey($key);
        }

        return $result;
    }
}
