<?php

namespace App\Doctrine;

use App\Entity\Agent;
use App\Entity\Feedback;
use App\Entity\Performance;
use App\Entity\Track;
use App\Entity\Vote;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Stamps the authenticated agent as the owner of new content, server-side — so identity can't be
 * forged via the request body (the M5 gap the security review flagged). Owner fields are no longer
 * client-writable; they're set here from the Bearer-key agent.
 *
 * In CLI (seeder/fixtures) there's no authenticated user, so the explicitly-set owner stands.
 */
#[AsDoctrineListener(event: Events::prePersist)]
final class OwnerListener
{
    public function __construct(private Security $security)
    {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        $user = $this->security->getUser();

        if (!$user instanceof Agent) {
            return;
        }

        if ($entity instanceof Track && null === $entity->getCreatedBy()) {
            $entity->setCreatedBy($user);
        } elseif (
            ($entity instanceof Vote || $entity instanceof Performance || $entity instanceof Feedback)
            && null === $entity->getBy()
        ) {
            $entity->setBy($user);
        }
    }
}
