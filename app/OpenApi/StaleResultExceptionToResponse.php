<?php

declare(strict_types=1);

namespace App\OpenApi;

use App\Exceptions\StaleResultException;
use Dedoc\Scramble\Extensions\ExceptionToResponseExtension;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types as OpenApiTypes;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;

/**
 * Documents {@see StaleResultException} as an HTTP 409 — the optimistic-locking
 * conflict raised when the submitted `expected_version` is stale (someone else
 * saved the same match in the meantime). Mirrors the render in bootstrap/app.php.
 */
class StaleResultExceptionToResponse extends ExceptionToResponseExtension
{
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof ObjectType
            && $type->isInstanceOf(StaleResultException::class);
    }

    public function toResponse(Type $type): Response
    {
        $body = (new OpenApiTypes\ObjectType)
            ->addProperty(
                'message',
                (new OpenApiTypes\StringType)->setDescription('Error overview.'),
            )
            ->addProperty(
                'expected_version',
                (new OpenApiTypes\IntegerType)->setDescription('The current version the client must reload before retrying.'),
            )
            ->setRequired(['message', 'expected_version']);

        return Response::make(409)
            ->setDescription('Version conflict — the match was changed by someone else. Reload and retry.')
            ->setContent('application/json', Schema::fromType($body));
    }
}
