<?php

declare(strict_types=1);

namespace IdentiSpec\Normalization;

use IdentiSpec\Contract\IdentifierNormalizer;
use IdentiSpec\Diagnostic\DiagnosticCode;
use IdentiSpec\Diagnostic\NormalizationTransformation;
use IdentiSpec\Diagnostic\ValidationIssue;
use IdentiSpec\Enum\LetterCasePolicy;
use IdentiSpec\Enum\ValidationMode;
use IdentiSpec\Normalization\Internal\AllowedSeparatorSet;
use IdentiSpec\Normalization\Internal\PrintableCharacter;
use SensitiveParameter;

final readonly class AsciiAlphanumericNormalizer implements IdentifierNormalizer
{
    private AllowedSeparatorSet $allowedSeparators;

    /**
     * @param list<string> $allowedSeparators
     */
    public function __construct(
        array $allowedSeparators = [],
        private LetterCasePolicy $letterCasePolicy = LetterCasePolicy::PRESERVE,
    ) {
        $this->allowedSeparators = AllowedSeparatorSet::forAlphanumeric($allowedSeparators);
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

            if (self::isDigit($character)) {
                $normalizedValue .= $character;

                continue;
            }

            if (self::isLetter($character)) {
                $caseResult = $this->normalizeCase($character, $position, $mode);

                if ($caseResult instanceof ValidationIssue) {
                    return NormalizationResult::failure([$caseResult], $transformations);
                }

                [$normalizedCharacter, $caseTransformation] = $caseResult;
                $normalizedValue .= $normalizedCharacter;

                if ($caseTransformation !== null) {
                    $transformations[] = $caseTransformation;
                }

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

    /**
     * @return array{string, NormalizationTransformation|null}|ValidationIssue
     */
    private function normalizeCase(
        string $character,
        int $position,
        ValidationMode $mode,
    ): array|ValidationIssue {
        $expectedCharacter = match ($this->letterCasePolicy) {
            LetterCasePolicy::PRESERVE => $character,
            LetterCasePolicy::UPPERCASE => strtoupper($character),
            LetterCasePolicy::LOWERCASE => strtolower($character),
        };

        if ($expectedCharacter === $character) {
            return [$character, null];
        }

        if ($mode === ValidationMode::STRICT) {
            return new ValidationIssue(
                DiagnosticCode::invalidCharacterCase(),
                $position,
                [
                    'actual' => $character,
                    'expected_case' => $this->letterCasePolicy->value,
                ],
            );
        }

        return [
            $expectedCharacter,
            new NormalizationTransformation(
                DiagnosticCode::normalizedCharacterCase(),
                $position,
                ['from' => $character, 'to' => $expectedCharacter],
            ),
        ];
    }

    private static function isDigit(string $character): bool
    {
        return $character >= '0' && $character <= '9';
    }

    private static function isLetter(string $character): bool
    {
        return ($character >= 'A' && $character <= 'Z')
            || ($character >= 'a' && $character <= 'z');
    }
}
