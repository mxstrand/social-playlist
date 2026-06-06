<?php

namespace App\Command;

use App\Service\CatalogSeeder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Seeds the starter catalog. Idempotent by default (skips if any tracks exist),
 * so it's safe to run on every prod boot/redeploy. Run by the prod entrypoint.
 */
#[AsCommand(name: 'app:seed', description: 'Seed the starter catalog if empty (idempotent).')]
final class SeedCommand extends Command
{
    public function __construct(private CatalogSeeder $seeder, private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Seed even if the catalog is not empty.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('force')) {
            $n = \count($this->seeder->seed()['tracks']);
            $this->em->flush();
            $io->success("Force-seeded {$n} tracks.");

            return Command::SUCCESS;
        }

        $n = $this->seeder->seedIfEmpty();
        if ($n > 0) {
            $io->success("Seeded starter catalog ({$n} tracks).");
        } else {
            $io->note('Catalog already has tracks — nothing to seed.');
        }

        return Command::SUCCESS;
    }
}
