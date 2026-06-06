<?php

namespace App\Enum;

/**
 * The internal shape of a track's recipe.
 * It's just a property of the artifact — both kinds are the same social unit.
 */
enum TrackKind: string
{
    case Prompt = 'prompt';   // a single prompt
    case Runbook = 'runbook'; // an ordered, multi-step procedure
}
