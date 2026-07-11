<?php

declare(strict_types=1);

namespace IdentiSpec\Validator\PL;

use DateTimeImmutable;
use IdentiSpec\Checksum\PL\NipChecksum;
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
use IdentiSpec\Enum\LetterCasePolicy;
use IdentiSpec\Enum\ValidationCapability;
use IdentiSpec\Enum\ValidationLevel;
use IdentiSpec\Normalization\AsciiAlphanumericNormalizer;
use IdentiSpec\ValidationOptions;
use IdentiSpec\ValidationResult;
use IdentiSpec\Value\IdentifierKey;
use LogicException;
use SensitiveParameter;

final readonly class VatEuValidator implements IdentifierTypeValidator
{
    private const LENGTH = 12;
    private const BODY_LENGTH = 10;

    private IdentifierDefinition $definition;
    private AsciiAlphanumericNormalizer $normalizer;
    private NipChecksum $checksum;

    public function __construct()
    {
        $this->definition = new IdentifierDefinition(
            IdentifierKey::fromParts('PL', 'VAT_EU'),
            'Polish EU VAT identification number',
            IdentifierCategory::TAX,
            new CanonicalFormat(
                'The uppercase PL prefix followed by exactly ten ASCII digits.',
                CharacterSet::ASCII_ALPHANUMERIC,
                [self::LENGTH],
                PrefixDefinition::required('PL'),
            ),
            ValidationLevel::FORMAT_AND_CHECKSUM,
            [
                ValidationCapability::CHARACTERS,
                ValidationCapability::LENGTH,
                ValidationCapability::PREFIX,
                ValidationCapability::CHECKSUM,
            ],
            new RuleSetMetadata(
                'PL_VAT_EU',
                '1.0.0',
                [
                    new RuleSource(
                        'European Commission — VAT identification numbers',
                        'https://taxation-customs.ec.europa.eu/vat-identification-numbers_en',
                    ),
                    new RuleSource(
                        'Polish Ministry of Finance — NIP check digit information',
                        'https://podatki-arch.mf.gov.pl/glos-podatnika-szczegoly-zgloszenia?application=114834',
                    ),
                ],
                new DateTimeImmutable('2026-07-11'),
            ),
        );
        $this->normalizer = new AsciiAlphanumericNormalizer(
            ['-', ' '],
            LetterCasePolicy::UPPERCASE,
        );
        $this->checksum = new NipChecksum();
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
        if (strlen($value) < 2 || strtoupper(substr($value, 0, 2)) !== 'PL') {
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
            throw new LogicException('Successful normalization must return a value.');
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

        $body = substr($normalizedValue, 2);
        $digitPrefixLength = strspn($body, '0123456789');

        if ($digitPrefixLength !== self::BODY_LENGTH) {
            return ValidationResult::invalid(
                $this->definition,
                $normalizedValue,
                [new ValidationIssue(DiagnosticCode::invalidEmbeddedValue(), $digitPrefixLength + 2)],
                transformations: $normalization->transformations(),
            );
        }

        if (!$this->checksum->isValid($body)) {
            return ValidationResult::invalid(
                $this->definition,
                $normalizedValue,
                [new ValidationIssue(DiagnosticCode::invalidChecksum(), 11)],
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
