<?php

namespace App\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Track;
use App\Entity\Vote;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Persists/removes a Vote via the default Doctrine processor, then recomputes
 * the affected track's denormalized score. This is how "cream rises".
 *
 * @implements ProcessorInterface<Vote, Vote|null>
 */
final class VoteProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private ProcessorInterface $removeProcessor,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        \assert($data instanceof Vote);
        $track = $data->getTrack();

        if ($operation instanceof DeleteOperationInterface) {
            $result = $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        } else {
            $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        if ($track instanceof Track) {
            $sum = (int) $this->em->createQuery(
                'SELECT COALESCE(SUM(v.value), 0) FROM App\Entity\Vote v WHERE v.track = :track'
            )->setParameter('track', $track)->getSingleScalarResult();

            $track->setScore($sum);
            $this->em->flush();
        }

        return $result;
    }
}
