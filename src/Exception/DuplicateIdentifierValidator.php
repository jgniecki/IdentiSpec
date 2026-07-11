<?php

declare(strict_types=1);

namespace IdentiSpec\Exception;

use LogicException;

final class DuplicateIdentifierValidator extends LogicException
{
    public static function forKey(string $key): self
    {
        return new self(sprintf('A validator is already registered for "%s".', $key));
    }
}
