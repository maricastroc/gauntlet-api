<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Standings;

/**
 * Tiebreaker criteria. The order in which they are applied lives in TiebreakRules,
 * so switching rulebooks means switching the list — not touching the engine.
 *
 * The scalars (Points/GoalDifference/GoalsFor/Wins) are compared globally.
 * HeadToHead is special: it rebuilds a mini-table only among the tied teams.
 */
enum Criterion
{
    case Points;
    case GoalDifference;
    case GoalsFor;
    case HeadToHead;
    case Wins;
}
