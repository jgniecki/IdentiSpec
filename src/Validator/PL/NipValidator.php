<?php

declare(strict_types=1);

namespace IdentiSpec\Validator\PL;

use DateTimeImmutable;
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

final readonly class NipValidator implements IdentifierTypeValidator
{
    private const LENGTH = 10;

    /** @var list<int> */
    private const WEIGHTS = [6, 5, 7, 2, 3, 4, 5, 6, 7];

    private IdentifierDefinition $definition;
    private NumericNormalizer $normalizer;

    public function __construct()
    {
        $this->definition = new IdentifierDefinition(
            IdentifierKey::fromParts('PL', 'NIP'),
            'Polish tax identification number',
            IdentifierCategory::TAX,
            new CanonicalFormat(
                'Exactly ten ASCII digits without the PL country prefix.',
                CharacterSet::DIGITS,
                [self::LENGTH],
                PrefixDefinition::forbidden('PL'),
            ),
            ValidationLevel::FORMAT_AND_CHECKSUM,
            [
                ValidationCapability::CHARACTERS,
                ValidationCapability::LENGTH,
                ValidationCapability::PREFIX,
                ValidationCapability::CHECKSUM,
            ],
            new RuleSetMetadata(
                'PL_NIP',
                '1.0.0',
                [
                    new RuleSource(
                        'Polish Ministry of Finance — NIP check digit information',
                        'https://podatki-arch.mf.gov.pl/glos-podatnika-szczegoly-zgloszenia?application=114834',
                    ),
                    new RuleSource(
                        'Algorytm.org — NIP checksum algorithm description',
                        'https://www.algorytm.org/numery-identyfikacyjne/nip.html',
                    ),
                ],
                new DateTimeImmutable('2026-07-11'),
            ),
        );
        $this->normalizer = new NumericNormalizer(['-', ' ']);
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
        if (strlen($value) >= 2 && strtoupper(substr($value, 0, 2)) === 'PL') {
            return ValidationResult::invalid(
                $this->definition,
                null,
                [new ValidationIssue(DiagnosticCode::invalidPrefix(), 0)],
            );
        }

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

        if ($normalizedValue === null) {
            throw new \LogicException('Successful normalization must return a value.');
        }

        if (strlen($normalizedValue) !== self::LENGTH) {
            return ValidationResult::invalid(
                $this->definition,
                $normalizedValue,
                [
                    new ValidationIssue(
                        DiagnosticCode::invalidLength(),
                        context: [
                            'expected' => self::LENGTH,
                            'actual' => strlen($normalizedValue),
                        ],
                    ),
                ],
                transformations: $normalization->transformations(),
            );
        }

        $remainder = 0;

        foreach (self::WEIGHTS as $position => $weight) {
            $remainder += ((int) $normalizedValue[$position]) * $weight;
        }

        $expectedCheckDigit = $remainder % 11;
        $actualCheckDigit = (int) $normalizedValue[9];

        if ($expectedCheckDigit === 10 || $expectedCheckDigit !== $actualCheckDigit) {
            return ValidationResult::invalid(
                $this->definition,
                $normalizedValue,
                [
                    new ValidationIssue(
                        DiagnosticCode::invalidChecksum(),
                        9,
                        [
                            'expected' => $expectedCheckDigit === 10 ? null : $expectedCheckDigit,
                            'actual' => $actualCheckDigit,
                        ],
                    ),
                ],
                transformations: $normalization->transformations(),
            );
        }

        return ValidationResult::valid(
            $this->definition,
            $normalizedValue,
            transformations: $normalization->transformations(),
        );
    }
}
