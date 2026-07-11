<?php

declare(strict_types=1);

namespace IdentiSpec\Exception;

use LogicException;

final class InconsistentValidationResult extends LogicException
{
    public static function mismatchedKey(string $expected, string $actual): self
    {
        return new self(sprintf(
            'Validator for "%s" returned a result for "%s".',
            $expected,
            $actual,
        ));
    }

    public static function unsupportedFromRegisteredValidator(string $key): self
    {
        return new self(sprintf(
            'Registered validator for "%s" returned UNSUPPORTED.',
            $key,
        ));
    }
}
