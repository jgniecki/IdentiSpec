<?php

declare(strict_types=1);

namespace IdentiSpec\Diagnostic;

use InvalidArgumentException;

/** @internal */
final class DiagnosticContext
{
    private function __construct() {}

    /**
     * @return array<string, scalar|null>
     */
    public static function normalize(mixed $context): array
    {
        if (!is_array($context)) {
            throw new InvalidArgumentException('Diagnostic context must be an array.');
        }

        $normalized = [];

        foreach ($context as $key => $value) {
            if (!is_string($key)) {
                throw new InvalidArgumentException('Diagnostic context keys must be strings.');
            }

            if ($value !== null && !is_scalar($value)) {
                throw new InvalidArgumentException('Diagnostic context values must be scalar or null.');
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
