<?php

declare(strict_types=1);

namespace IdentiSpec\Validator\PL;

use DateTimeImmutable;
use IdentiSpec\Contract\IdentifierNormalizer;
use IdentiSpec\Contract\IdentifierTypeValidator;
use IdentiSpec\Definition\CanonicalFormat;
use IdentiSpec\Definition\IdentifierDefinition;
use IdentiSpec\Definition\PrefixDefinition;
use IdentiSpec\Definition\RuleSetMetadata;
use IdentiSpec\Definition\RuleSource;
use IdentiSpec\Diagnostic\DiagnosticCode;
use IdentiSpec\Diagnostic\ValidationIssue;
use IdentiSpec\Enum\CharacterSet;
use IdentiSpec\Enum\IdentifierCategory;
use IdentiSpec\Enum\ValidationCapability;
use IdentiSpec\Enum\ValidationLevel;
use IdentiSpec\Normalization\NumericNormalizer;
use IdentiSpec\ValidationOptions;
use IdentiSpec\ValidationResult;
use IdentiSpec\Value\IdentifierKey;
use SensitiveParameter;

final readonly class PeselValidator implements IdentifierTypeValidator
{
    private const CANONICAL_LENGTH = 11;

    /** @var non-empty-list<positive-int> */
    private const CHECKSUM_WEIGHTS = [1, 3, 7, 9, 1, 3, 7, 9, 1, 3];

    private IdentifierDefinition $definition;
    private IdentifierNormalizer $normalizer;

    public function __construct()
    {
        $this->definition = self::createDefinition();
        $this->normalizer = new NumericNormalizer();
    }

    public function definition(): IdentifierDefinition
    {
        return $this->definition;
    }

    public function validate(
        #[SensitiveParameter]
        string $value,
        ValidationOptions $options,
    ): ValidationResult {
        $normalization = $this->normalizer->normalize($value, $options->mode());

        if (!$normalization->isSuccessful()) {
            return ValidationResult::invalid(
                $this->definition,
                null,
                $normalization->issues(),
                transformations: $normalization->transformations(),
            );
        }

        $normalizedValue = $normalization->normalizedValue();
        assert($normalizedValue !== null);

        if (strlen($normalizedValue) !== self::CANONICAL_LENGTH) {
            return ValidationResult::invalid(
                $this->definition,
                $normalizedValue,
                [new ValidationIssue(DiagnosticCode::invalidLength())],
            );
        }

        if (!self::hasValidEmbeddedBirthDate($normalizedValue)) {
            return ValidationResult::invalid(
                $this->definition,
                $normalizedValue,
                [new ValidationIssue(DiagnosticCode::invalidEmbeddedValue(), 0)],
            );
        }

        if (self::calculateCheckDigit($normalizedValue) !== (int) $normalizedValue[10]) {
            return ValidationResult::invalid(
                $this->definition,
                $normalizedValue,
                [new ValidationIssue(DiagnosticCode::invalidChecksum(), 10)],
            );
        }

        return ValidationResult::valid($this->definition, $normalizedValue);
    }

    private static function hasValidEmbeddedBirthDate(#[SensitiveParameter] string $value): bool
    {
        $year = (int) substr($value, 0, 2);
        $encodedMonth = (int) substr($value, 2, 2);
        $day = (int) substr($value, 4, 2);

        [$century, $monthOffset] = match (true) {
            $encodedMonth >= 81 && $encodedMonth <= 92 => [1800, 80],
            $encodedMonth >= 1 && $encodedMonth <= 12 => [1900, 0],
            $encodedMonth >= 21 && $encodedMonth <= 32 => [2000, 20],
            $encodedMonth >= 41 && $encodedMonth <= 52 => [2100, 40],
            $encodedMonth >= 61 && $encodedMonth <= 72 => [2200, 60],
            default => [0, 0],
        };

        if ($century === 0) {
            return false;
        }

        return checkdate($encodedMonth - $monthOffset, $day, $century + $year);
    }

    private static function calculateCheckDigit(#[SensitiveParameter] string $value): int
    {
        $sum = 0;

        foreach (self::CHECKSUM_WEIGHTS as $position => $weight) {
            $sum += ((int) $value[$position]) * $weight;
        }

        return (10 - ($sum % 10)) % 10;
    }

    private static function createDefinition(): IdentifierDefinition
    {
        return new IdentifierDefinition(
            IdentifierKey::fromParts('PL', 'PESEL'),
            'Polish Universal Electronic System for Registration of the Population number (PESEL)',
            IdentifierCategory::PERSONAL_ID,
            new CanonicalFormat(
                'Eleven ASCII digits containing an encoded birth date and a final checksum digit.',
                CharacterSet::DIGITS,
                [self::CANONICAL_LENGTH],
                PrefixDefinition::none(),
            ),
            ValidationLevel::FULL_OFFLINE_RULES,
            [
                ValidationCapability::CHARACTERS,
                ValidationCapability::LENGTH,
                ValidationCapability::STRUCTURE,
                ValidationCapability::CHECKSUM,
                ValidationCapability::EMBEDDED_SEMANTICS,
            ],
            new RuleSetMetadata(
                'PL_PESEL',
                '1.0.0',
                [
                    new RuleSource(
                        'Gov.pl — What is a PESEL number',
                        'https://www.gov.pl/web/gov/czym-jest-numer-pesel',
                    ),
                    new RuleSource(
                        'Polish Population Registration Act — consolidated text',
                        'https://eli.gov.pl/api/acts/DU/2024/736/text.html',
                    ),
                ],
                new DateTimeImmutable('2026-07-11'),
            ),
        );
    }
}
