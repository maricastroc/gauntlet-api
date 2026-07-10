<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Standings;

/**
 * The ordered chain of tiebreaker criteria.
 * Named constructors per rulebook; ::of() for test scenarios.
 */
final class TiebreakRules
{
    /** @param Criterion[] $criteria */
    private function __construct(public readonly array $criteria) {}

    /**
     * World Cup style order: points, overall goal difference, overall goals for,
     * then head-to-head among the tied teams and, finally, number of wins.
     * (Fair play and drawing of lots stay out of the pure engine — one is external data, the other is random.)
     */
    public static function fifa(): self
    {
        return new self([
            Criterion::Points,
            Criterion::GoalDifference,
            Criterion::GoalsFor,
            Criterion::HeadToHead,
            Criterion::Wins,
        ]);
    }

    public static function of(Criterion ...$criteria): self
    {
        return new self($criteria);
    }
}
