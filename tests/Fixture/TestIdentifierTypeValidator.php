<?php

declare(strict_types=1);

namespace IdentiSpec\Tests\Fixture;

use DateTimeImmutable;
use IdentiSpec\Contract\IdentifierTypeValidator;
use IdentiSpec\Definition\CanonicalFormat;
use IdentiSpec\Definition\IdentifierDefinition;
use IdentiSpec\Definition\RuleSetMetadata;
use IdentiSpec\Definition\RuleSource;
use IdentiSpec\Diagnostic\DiagnosticCode;
use IdentiSpec\Diagnostic\ValidationIssue;
use IdentiSpec\Enum\CharacterSet;
use IdentiSpec\Enum\IdentifierCategory;
use IdentiSpec\Enum\ValidationCapability;
use IdentiSpec\Enum\ValidationLevel;
use IdentiSpec\ValidationOptions;
use IdentiSpec\ValidationResult;
use IdentiSpec\Value\IdentifierKey;

final class TestIdentifierTypeValidator implements IdentifierTypeValidator
{
    private readonly IdentifierDefinition $definition;
    private ?string $lastValue = null;
    private ?ValidationOptions $lastOptions = null;

    public function __construct(string $jurisdictionCode = 'XX', string $identifierType = 'TEST')
    {
        $this->definition = self::definitionFor($jurisdictionCode, $identifierType);
    }

    public static function definitionFor(
        string $jurisdictionCode = 'XX',
        string $identifierType = 'TEST',
    ): IdentifierDefinition {
        $key = IdentifierKey::fromParts($jurisdictionCode, $identifierType);

        return new IdentifierDefinition(
            $key,
            'Test identifier',
            IdentifierCategory::CUSTOM,
            new CanonicalFormat('The literal ASCII value OK.', CharacterSet::CUSTOM, [2]),
            ValidationLevel::FORMAT_ONLY,
            [ValidationCapability::CHARACTERS, ValidationCapability::LENGTH],
            new RuleSetMetadata(
                $key->jurisdictionCode() . '_' . $key->identifierType(),
                '1.0.0',
                [new RuleSource('Internal test fixture', 'tests/Fixture/TestIdentifierTypeValidator.php')],
                new DateTimeImmutable('2026-07-11'),
            ),
        );
    }

    public function definition(): IdentifierDefinition
    {
        return $this->definition;
    }

    public function validate(string $value, ValidationOptions $options): ValidationResult
    {
        $this->lastValue = $value;
        $this->lastOptions = $options;

        if ($value === 'OK') {
            return ValidationResult::valid($this->definition, $value);
        }

        return ValidationResult::invalid(
            $this->definition,
            null,
            [new ValidationIssue(DiagnosticCode::invalidFormat())],
        );
    }

    public function lastValue(): ?string
    {
        return $this->lastValue;
    }

    public function lastOptions(): ?ValidationOptions
    {
        return $this->lastOptions;
    }
}
