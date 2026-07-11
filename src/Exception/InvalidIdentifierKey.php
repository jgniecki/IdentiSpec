<?php

declare(strict_types=1);

namespace IdentiSpec\Exception;

use InvalidArgumentException;

final class InvalidIdentifierKey extends InvalidArgumentException
{
    public static function forPart(string $part): self
    {
        return new self(sprintf(
            'Identifier key %s must use uppercase ASCII letters, digits, underscores, or hyphens and start with a letter.',
            $part,
        ));
    }
}
