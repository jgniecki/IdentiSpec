<?php

declare(strict_types=1);

namespace IdentiSpec\Validator\PL;

use DateTimeImmutable;
use IdentiSpec\Checksum\WeightedModulo;
use IdentiSpec\Contract\ChecksumAlgorithm;
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
use IdentiSpec\Enum\ValidationMode;
use IdentiSpec\Normalization\NumericNormalizer;
use IdentiSpec\ValidationOptions;
use IdentiSpec\ValidationResult;
use IdentiSpec\Value\IdentifierKey;
use SensitiveParameter;

final readonly class NipValidator implements IdentifierTypeValidator
{
    private const CANONICAL_LENGTH = 10;

    private IdentifierDefinition $definition;
    private IdentifierNormalizer $normalizer;
    private ChecksumAlgorithm $checksum;

    public function __construct()
    {
        $this->definition = self::createDefinition();
        $this->normalizer = new NumericNormalizer(['-', ' ']);
        $this->checksum = new WeightedModulo([6, 5, 7, 2, 3, 4, 5, 6, 7], 11);
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
        if (str_starts_with($value, 'PL')) {
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
        assert($normalizedValue !== null);

        if (
            $options->mode() === ValidationMode::LENIENT
            && $normalization->transformations() !== []
            && preg_match('/\A[0-9]{3}([ -])[0-9]{3}\1[0-9]{2}\1[0-9]{2}\z/', $value) !== 1
        ) {
            return ValidationResult::invalid(
                $this->definition,
                $normalizedValue,
                [new ValidationIssue(DiagnosticCode::normalizationNotAllowed())],
                transformations: $normalization->transformations(),
            );
        }

        if (strlen($normalizedValue) !== self::CANONICAL_LENGTH) {
            return ValidationResult::invalid(
                $this->definition,
                $normalizedValue,
                [new ValidationIssue(DiagnosticCode::invalidLength())],
                transformations: $normalization->transformations(),
            );
        }

        $expectedCheckDigit = $this->checksum->calculate(substr($normalizedValue, 0, 9));
        $actualCheckDigit = (int) $normalizedValue[9];

        if ($expectedCheckDigit === 10 || $expectedCheckDigit !== $actualCheckDigit) {
            return ValidationResult::invalid(
                $this->definition,
                $normalizedValue,
                [new ValidationIssue(DiagnosticCode::invalidChecksum(), 9)],
                transformations: $normalization->transformations(),
            );
        }

        return ValidationResult::valid(
            $this->definition,
            $normalizedValue,
            transformations: $normalization->transformations(),
        );
    }

    private static function createDefinition(): IdentifierDefinition
    {
        return new IdentifierDefinition(
            IdentifierKey::fromParts('PL', 'NIP'),
            'Polish Tax Identification Number (NIP)',
            IdentifierCategory::TAX,
            new CanonicalFormat(
                'Ten ASCII digits; the tenth digit is a checksum. The PL prefix is forbidden.',
                CharacterSet::DIGITS,
                [self::CANONICAL_LENGTH],
                PrefixDefinition::forbidden('PL'),
            ),
            ValidationLevel::FORMAT_AND_CHECKSUM,
            [
                ValidationCapability::CHARACTERS,
                ValidationCapability::LENGTH,
                ValidationCapability::PREFIX,
                ValidationCapability::STRUCTURE,
                ValidationCapability::CHECKSUM,
            ],
            new RuleSetMetadata(
                'PL_NIP',
                '1.0.0',
                [
                    new RuleSource(
                        'Polish Ministry of Finance — Check NIP status',
                        'https://sprawdznip.podatki.gov.pl/',
                    ),
                    new RuleSource(
                        'European Commission — Format and structure of TINs',
                        'https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:52016XC1223(02)',
                    ),
                    new RuleSource(
                        'Algorytm.org — NIP checksum implementation',
                        'https://www.algorytm.org/numery-identyfikacyjne/nip/nip-d.html',
                    ),
                ],
                new DateTimeImmutable('2026-07-11'),
            ),
        );
    }
}
