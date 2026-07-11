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

    public static function mismatchedLevel(string $key, string $expected, ?string $actual): self
    {
        return new self(sprintf(
            'Validator for "%s" returned validation level "%s" instead of "%s".',
            $key,
            $actual ?? 'null',
            $expected,
        ));
    }

    public static function missingMetadata(string $key): self
    {
        return new self(sprintf(
            'Validator for "%s" returned a result without rule-set metadata.',
            $key,
        ));
    }

    public static function mismatchedRuleSet(
        string $key,
        string $expectedId,
        string $expectedVersion,
        string $actualId,
        string $actualVersion,
    ): self {
        return new self(sprintf(
            'Validator for "%s" returned rule set "%s@%s" instead of "%s@%s".',
            $key,
            $actualId,
            $actualVersion,
            $expectedId,
            $expectedVersion,
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
