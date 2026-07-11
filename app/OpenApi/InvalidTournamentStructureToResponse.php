<?php

declare(strict_types=1);

namespace App\OpenApi;

use App\Exceptions\InvalidTournamentStructure;
use Dedoc\Scramble\Extensions\ExceptionToResponseExtension;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types as OpenApiTypes;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;

/**
 * Documents {@see InvalidTournamentStructure} as an HTTP 422 — raised when a stage
 * transition is attempted on an incomplete/invalid structure (e.g. generating the
 * knockout before every group is decided). Mirrors the render in bootstrap/app.php.
 */
class InvalidTournamentStructureToResponse extends ExceptionToResponseExtension
{
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof ObjectType
            && $type->isInstanceOf(InvalidTournamentStructure::class);
    }

    public function toResponse(Type $type): Response
    {
        $body = (new OpenApiTypes\ObjectType)
            ->addProperty(
                'message',
                (new OpenApiTypes\StringType)->setDescription('Why the tournament structure rejected the transition.'),
            )
            ->setRequired(['message']);

        return Response::make(422)
            ->setDescription('The tournament structure does not allow this transition yet.')
            ->setContent('application/json', Schema::fromType($body));
    }
}
