<?php

declare(strict_types=1);

namespace IdentiSpec\Normalization;

use IdentiSpec\Contract\IdentifierNormalizer;
use IdentiSpec\Diagnostic\DiagnosticCode;
use IdentiSpec\Diagnostic\NormalizationTransformation;
use IdentiSpec\Diagnostic\ValidationIssue;
use IdentiSpec\Enum\ValidationMode;
use InvalidArgumentException;

final readonly class NumericNormalizer implements IdentifierNormalizer
{
    /** @var list<string> */
    private array $allowedSeparators;

    /**
     * @param list<string> $allowedSeparators
     */
    public function __construct(array $allowedSeparators = [])
    {
        $uniqueSeparators = [];

        foreach ($allowedSeparators as $separator) {
            if (strlen($separator) !== 1) {
                throw new InvalidArgumentException('Every separator must contain exactly one byte.');
            }

            if ($separator >= '0' && $separator <= '9') {
                throw new InvalidArgumentException('A digit cannot be configured as a separator.');
            }

            $uniqueSeparators[$separator] = $separator;
        }

        $this->allowedSeparators = array_values($uniqueSeparators);
    }

    public function normalize(string $value, ValidationMode $mode): NormalizationResult
    {
        if ($value === '') {
            return NormalizationResult::failure([
                new ValidationIssue(DiagnosticCode::emptyValue()),
            ]);
        }

        $normalizedValue = '';
        $transformations = [];

        for ($position = 0, $length = strlen($value); $position < $length; ++$position) {
            $character = $value[$position];

            if ($character >= '0' && $character <= '9') {
                $normalizedValue .= $character;

                continue;
            }

            if (
                $mode === ValidationMode::LENIENT
                && in_array($character, $this->allowedSeparators, true)
            ) {
                $transformations[] = new NormalizationTransformation(
                    DiagnosticCode::removedSeparator(),
                    $position,
                    ['separator' => self::printable($character)],
                );

                continue;
            }

            return NormalizationResult::failure(
                [
                    new ValidationIssue(
                        DiagnosticCode::invalidCharacter(),
                        $position,
                        ['character' => self::printable($character)],
                    ),
                ],
                $transformations,
            );
        }

        if ($normalizedValue === '') {
            return NormalizationResult::failure(
                [new ValidationIssue(DiagnosticCode::emptyValue())],
                $transformations,
            );
        }

        return NormalizationResult::success($normalizedValue, $transformations);
    }

    private static function printable(string $character): string
    {
        return match ($character) {
            ' ' => 'SPACE',
            "\t" => 'TAB',
            "\n" => 'LF',
            "\r" => 'CR',
            default => ord($character) >= 32 && ord($character) <= 126
                ? $character
                : sprintf('0x%02X', ord($character)),
        };
    }
}
