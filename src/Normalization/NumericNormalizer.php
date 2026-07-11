<?php

declare(strict_types=1);

namespace IdentiSpec\Normalization;

use IdentiSpec\Contract\IdentifierNormalizer;
use IdentiSpec\Diagnostic\DiagnosticCode;
use IdentiSpec\Diagnostic\NormalizationTransformation;
use IdentiSpec\Diagnostic\ValidationIssue;
use IdentiSpec\Enum\ValidationMode;
use IdentiSpec\Normalization\Internal\AllowedSeparatorSet;
use IdentiSpec\Normalization\Internal\PrintableCharacter;
use SensitiveParameter;

final readonly class NumericNormalizer implements IdentifierNormalizer
{
    private AllowedSeparatorSet $allowedSeparators;

    /**
     * @param list<string> $allowedSeparators
     */
    public function __construct(array $allowedSeparators = [])
    {
        $this->allowedSeparators = AllowedSeparatorSet::forNumeric($allowedSeparators);
    }

    public function normalize(
        #[SensitiveParameter]
        string $value,
        ValidationMode $mode,
    ): NormalizationResult {
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
                && $this->allowedSeparators->contains($character)
            ) {
                $transformations[] = new NormalizationTransformation(
                    DiagnosticCode::removedSeparator(),
                    $position,
                    ['separator' => PrintableCharacter::fromByte($character)],
                );

                continue;
            }

            return NormalizationResult::failure(
                [
                    new ValidationIssue(
                        DiagnosticCode::invalidCharacter(),
                        $position,
                        ['character' => PrintableCharacter::fromByte($character)],
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
}
