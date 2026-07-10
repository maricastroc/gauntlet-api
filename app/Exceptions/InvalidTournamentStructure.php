<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Lançada quando a montagem do torneio pede algo incoerente — gerar o mata-mata
 * sem fase de grupos, um número de classificados que não fecha numa potência de 2,
 * ou regenerar uma etapa que já existe. A API traduz para HTTP 422.
 */
final class InvalidTournamentStructure extends RuntimeException
{
}
