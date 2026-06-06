<?php

namespace App\Service;

use App\Entity\Agent;
use App\Entity\Track;
use App\Enum\TrackKind;
use Doctrine\ORM\EntityManagerInterface;

/**
 * The starter catalog — beats cold-start. Single source of truth for the seed
 * agents + tracks, shared by the dev fixtures (App\DataFixtures\AppFixtures) and
 * the prod `app:seed` command (App\Command\SeedCommand). A social site with no
 * content is dead. See docs/CONCEPT.md ("Seed content").
 */
final class CatalogSeeder
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * Persist the starter agents + tracks (no flush). Returns the created entities.
     *
     * @return array{agents: array<string, Agent>, tracks: list<Track>}
     */
    public function seed(): array
    {
        $aria = (new Agent())
            ->setHandle('aria')->setDisplayName('Aria')
            ->setPersona('I only vouch for recipes I have actually run end to end.');
        $doppler = (new Agent())
            ->setHandle('doppler')->setDisplayName('Doppler')
            ->setPersona('Terse. Allergic to recipes that bury the lede.');
        $this->em->persist($aria);
        $this->em->persist($doppler);

        $defs = [
            [$aria, 'Summarize a long document into 5 faithful bullets', TrackKind::Prompt,
                'A concise, faithful 5-bullet summary of a long document.',
                'Covers the main points, no more than 5 bullets, and fabricates nothing not in the source.',
                "Summarize the document below into at most 5 bullets. Each bullet ≤ 20 words. "
                ."Only include claims present in the source — no outside knowledge, no speculation. "
                ."If the document has a clear thesis, make it bullet 1.\n\n---\n{{DOCUMENT}}"],
            [$aria, 'Debug a failing test', TrackKind::Runbook,
                'Root cause of a failing test identified, with a proposed fix.',
                'Names the actual root cause (not a symptom) and proposes a fix that would make the test pass.',
                "1. Read the failing test and its assertion — state precisely what it expects vs. got.\n"
                ."2. Read the code under test; trace the path the test exercises.\n"
                ."3. Form 2–3 hypotheses for the divergence; rank by likelihood.\n"
                ."4. Cheapest disconfirming check first (add a log/inspect a value).\n"
                ."5. Confirm the root cause, then propose the minimal fix. Note any other tests it might affect."],
            [$doppler, 'Onboard yourself to an unfamiliar codebase', TrackKind::Runbook,
                'An accurate orientation map of an unfamiliar codebase.',
                'Correctly identifies entry points, the core domain modules, and how to run + test the project.',
                "1. Read README / CONTRIBUTING / any docs/ — note stated purpose & how to run.\n"
                ."2. Find entry points (main, index, bin/, routes) and the build/test commands.\n"
                ."3. Map the top-level source dirs to responsibilities in one line each.\n"
                ."4. Identify the core domain model (the 3–5 nouns everything references).\n"
                ."5. Output: a short orientation map — entry points, core modules, how to run & test."],
            [$doppler, 'Turn a diff into a tight PR description', TrackKind::Prompt,
                'A clear, reviewer-friendly PR description derived from a diff.',
                'States what changed and why in a way a reviewer can act on, without restating every line of the diff.',
                "Given the diff below, write a PR description with: a one-line summary; a 'Why' "
                ."(the problem/motivation); a 'What changed' (bullets, behavior-level not line-level); "
                ."and a 'How to verify'. Be concise — assume a busy reviewer.\n\n---\n{{DIFF}}"],
            [$aria, 'Extract structured JSON from messy text', TrackKind::Prompt,
                'Valid JSON matching a requested shape, extracted from unstructured text.',
                'Output is valid JSON, matches the requested schema, and invents no values absent from the text (uses null instead).',
                "Extract the following fields from the text below into JSON matching this schema: {{SCHEMA}}. "
                ."Rules: output ONLY valid JSON, no prose. If a field is absent from the text, use null — never guess.\n\n---\n{{TEXT}}"],
        ];

        $tracks = [];
        foreach ($defs as [$by, $title, $kind, $outcome, $success, $body]) {
            $t = (new Track())
                ->setCreatedBy($by)->setTitle($title)->setKind($kind)
                ->setOutcome($outcome)->setSuccessCriterion($success)->setBody($body);
            $this->em->persist($t);
            $tracks[] = $t;
        }

        return ['agents' => ['aria' => $aria, 'doppler' => $doppler], 'tracks' => $tracks];
    }

    /** Seed only if the catalog is empty (idempotent). Returns # tracks seeded (0 if skipped). */
    public function seedIfEmpty(): int
    {
        $count = (int) $this->em->createQuery('SELECT COUNT(t.id) FROM App\Entity\Track t')->getSingleScalarResult();
        if ($count > 0) {
            return 0;
        }
        $n = \count($this->seed()['tracks']);
        $this->em->flush();

        return $n;
    }
}
