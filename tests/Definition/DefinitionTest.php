<?php

declare(strict_types=1);

namespace IdentiSpec\Tests\Definition;

use DateTimeImmutable;
use IdentiSpec\Definition\CanonicalFormat;
use IdentiSpec\Definition\IdentifierDefinition;
use IdentiSpec\Definition\RuleSetMetadata;
use IdentiSpec\Definition\RuleSource;
use IdentiSpec\Enum\CharacterSet;
use IdentiSpec\Enum\IdentifierCategory;
use IdentiSpec\Enum\ValidationCapability;
use IdentiSpec\Enum\ValidationLevel;
use IdentiSpec\Tests\Fixture\TestIdentifierTypeValidator;
use IdentiSpec\Value\IdentifierKey;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class DefinitionTest extends TestCase
{
    public function testCanonicalFormatIsDescriptiveAndDeterministic(): void
    {
        $format = new CanonicalFormat(
            'PL followed by ten digits.',
            CharacterSet::ASCII_ALPHANUMERIC,
            [12, 10, 12],
            'PL',
        );

        self::assertSame([10, 12], $format->lengths());
        self::assertSame('PL', $format->literalPrefix());
        self::assertSame(CharacterSet::ASCII_ALPHANUMERIC, $format->characterSet());
        self::assertSame('PL followed by ten digits.', $format->description());
    }

    public function testDefinitionValuesAreTrimmedAndExposedExactly(): void
    {
        $source = new RuleSource(' Official source ', ' reference ');
        $metadata = new RuleSetMetadata(
            ' XX_TEST ',
            ' 1.0.0 ',
            [$source],
            new DateTimeImmutable('2026-07-11'),
            new DateTimeImmutable('2026-01-01'),
            new DateTimeImmutable('2026-12-31'),
        );
        $format = new CanonicalFormat(
            ' Example format. ',
            CharacterSet::ASCII_ALPHANUMERIC,
            [2, 1],
            ' X ',
        );
        $definition = new IdentifierDefinition(
            IdentifierKey::fromParts('XX', 'TRIMMED'),
            ' Trimmed name ',
            IdentifierCategory::CUSTOM,
            $format,
            ValidationLevel::FORMAT_ONLY,
            [ValidationCapability::LENGTH, ValidationCapability::CHARACTERS, ValidationCapability::PREFIX],
            $metadata,
        );

        self::assertSame('Example format.', $format->description());
        self::assertSame([1, 2], $format->lengths());
        self::assertSame('X', $format->literalPrefix());
        self::assertSame('Official source', $source->name());
        self::assertSame('reference', $source->reference());
        self::assertSame('XX_TEST', $metadata->ruleSetId());
        self::assertSame('1.0.0', $metadata->version());
        self::assertSame([$source], $metadata->sources());
        self::assertSame('Trimmed name', $definition->displayName());
        self::assertSame(
            [ValidationCapability::LENGTH, ValidationCapability::CHARACTERS, ValidationCapability::PREFIX],
            $definition->capabilities(),
        );
    }

    public function testCanonicalBoundariesAndEqualEffectiveDatesAreAccepted(): void
    {
        self::assertSame([1], (new CanonicalFormat('One.', CharacterSet::DIGITS, [1], '9'))->lengths());

        $date = new DateTimeImmutable('2026-07-11');
        $metadata = new RuleSetMetadata(
            'XX_TEST',
            '1',
            [new RuleSource('Source', 'ref')],
            $date,
            $date,
            $date,
        );

        self::assertSame($date, $metadata->effectiveFrom());
        self::assertSame($date, $metadata->effectiveTo());
    }

    public function testDefinitionRejectsDuplicateCapabilities(): void
    {
        $base = TestIdentifierTypeValidator::definitionFor();
        $this->expectException(InvalidArgumentException::class);

        new IdentifierDefinition(
            $base->key(),
            $base->displayName(),
            $base->category(),
            $base->canonicalFormat(),
            $base->validationLevel(),
            [
                ValidationCapability::CHARACTERS,
                ValidationCapability::CHARACTERS,
                ValidationCapability::LENGTH,
            ],
            $base->metadata(),
        );

    }

    public function testDefinitionReportsCapability(): void
    {
        $definition = TestIdentifierTypeValidator::definitionFor();

        self::assertTrue($definition->hasCapability(ValidationCapability::CHARACTERS));
        self::assertFalse($definition->hasCapability(ValidationCapability::CHECKSUM));
    }

    public function testDefinitionRequiresCoreCapabilities(): void
    {
        $base = TestIdentifierTypeValidator::definitionFor();

        $this->expectException(InvalidArgumentException::class);

        new IdentifierDefinition(
            $base->key(),
            $base->displayName(),
            $base->category(),
            $base->canonicalFormat(),
            $base->validationLevel(),
            [ValidationCapability::CHARACTERS],
            $base->metadata(),
        );
    }

    public function testDefinitionRequiresCharactersCapabilityIndependently(): void
    {
        $base = TestIdentifierTypeValidator::definitionFor();
        $this->expectException(InvalidArgumentException::class);

        new IdentifierDefinition(
            $base->key(),
            $base->displayName(),
            $base->category(),
            $base->canonicalFormat(),
            $base->validationLevel(),
            [ValidationCapability::LENGTH],
            $base->metadata(),
        );
    }

    public function testFormatAndChecksumRequiresChecksumCapability(): void
    {
        $base = TestIdentifierTypeValidator::definitionFor();

        $this->expectException(InvalidArgumentException::class);

        new IdentifierDefinition(
            $base->key(),
            $base->displayName(),
            $base->category(),
            $base->canonicalFormat(),
            ValidationLevel::FORMAT_AND_CHECKSUM,
            [ValidationCapability::CHARACTERS, ValidationCapability::LENGTH],
            $base->metadata(),
        );
    }

    public function testFormatOnlyRejectsChecksumCapability(): void
    {
        $base = TestIdentifierTypeValidator::definitionFor();

        $this->expectException(InvalidArgumentException::class);

        new IdentifierDefinition(
            $base->key(),
            $base->displayName(),
            $base->category(),
            $base->canonicalFormat(),
            ValidationLevel::FORMAT_ONLY,
            [
                ValidationCapability::CHARACTERS,
                ValidationCapability::LENGTH,
                ValidationCapability::CHECKSUM,
            ],
            $base->metadata(),
        );
    }

    public function testLiteralPrefixRequiresPrefixCapability(): void
    {
        $base = TestIdentifierTypeValidator::definitionFor();

        $this->expectException(InvalidArgumentException::class);

        new IdentifierDefinition(
            IdentifierKey::fromParts('XX', 'PREFIXED'),
            'Prefixed test identifier',
            IdentifierCategory::CUSTOM,
            new CanonicalFormat('XX plus two characters.', CharacterSet::ASCII_ALPHANUMERIC, [4], 'XX'),
            ValidationLevel::FORMAT_ONLY,
            [ValidationCapability::CHARACTERS, ValidationCapability::LENGTH],
            $base->metadata(),
        );
    }

    public function testLiteralPrefixWithCapabilityIsValid(): void
    {
        $base = TestIdentifierTypeValidator::definitionFor();
        $definition = new IdentifierDefinition(
            IdentifierKey::fromParts('XX', 'PREFIXED'),
            'Prefixed test identifier',
            IdentifierCategory::CUSTOM,
            new CanonicalFormat('XX plus two characters.', CharacterSet::ASCII_ALPHANUMERIC, [4], 'XX'),
            ValidationLevel::FORMAT_ONLY,
            [
                ValidationCapability::CHARACTERS,
                ValidationCapability::LENGTH,
                ValidationCapability::PREFIX,
            ],
            $base->metadata(),
        );

        self::assertTrue($definition->hasCapability(ValidationCapability::PREFIX));
    }

    public function testFullOfflineRulesAllowsNoChecksum(): void
    {
        $base = TestIdentifierTypeValidator::definitionFor();
        $definition = new IdentifierDefinition(
            $base->key(),
            $base->displayName(),
            $base->category(),
            $base->canonicalFormat(),
            ValidationLevel::FULL_OFFLINE_RULES,
            [
                ValidationCapability::CHARACTERS,
                ValidationCapability::LENGTH,
                ValidationCapability::EMBEDDED_SEMANTICS,
            ],
            $base->metadata(),
        );

        self::assertFalse($definition->hasCapability(ValidationCapability::CHECKSUM));
    }

    public function testDigitsFormatRejectsLetterPrefix(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CanonicalFormat('Invalid numeric format.', CharacterSet::DIGITS, [4], 'XX');
    }

    public function testFormatRejectsPrefixLongerThanLength(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CanonicalFormat('Invalid short format.', CharacterSet::ASCII_ALPHANUMERIC, [1], 'XX');
    }

    public function testDefinitionDoesNotExposeAlgorithmSelector(): void
    {
        $properties = array_map(
            static fn(\ReflectionProperty $property): string => $property->getName(),
            (new ReflectionClass(IdentifierDefinition::class))->getProperties(),
        );

        self::assertNotContains('checksumAlgorithm', $properties);
    }

    public function testRejectsReversedEffectivePeriod(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RuleSetMetadata(
            'XX_TEST',
            '1.0.0',
            [new RuleSource('Test source', 'test-reference')],
            new DateTimeImmutable('2026-07-11'),
            new DateTimeImmutable('2026-12-31'),
            new DateTimeImmutable('2026-01-01'),
        );
    }

    public function testRejectsDuplicateRuleSourceReference(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RuleSetMetadata(
            'XX_TEST',
            '1.0.0',
            [
                new RuleSource('First source', 'same-reference'),
                new RuleSource('Second source', 'same-reference'),
            ],
            new DateTimeImmutable('2026-07-11'),
        );
    }

    public function testRuleSetRequiresAtLeastOneSource(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RuleSetMetadata('XX_TEST', '1', [], new DateTimeImmutable('2026-07-11'));
    }

    public function testOpenEndedEffectivePeriodsAreAccepted(): void
    {
        $source = [new RuleSource('Source', 'ref')];
        $from = new RuleSetMetadata(
            'XX_FROM',
            '1',
            $source,
            new DateTimeImmutable('2026-07-11'),
            new DateTimeImmutable('2026-01-01'),
        );
        $to = new RuleSetMetadata(
            'XX_TO',
            '1',
            $source,
            new DateTimeImmutable('2026-07-11'),
            null,
            new DateTimeImmutable('2026-12-31'),
        );

        self::assertNull($from->effectiveTo());
        self::assertNull($to->effectiveFrom());
    }

    public function testRuntimeRejectsInvalidCapabilityObject(): void
    {
        $base = TestIdentifierTypeValidator::definitionFor();
        $reflection = new ReflectionClass(IdentifierDefinition::class);

        $this->expectException(InvalidArgumentException::class);

        $reflection->newInstanceArgs([
            $base->key(),
            $base->displayName(),
            $base->category(),
            $base->canonicalFormat(),
            $base->validationLevel(),
            [new \stdClass()],
            $base->metadata(),
        ]);
    }

    public function testRuntimeRejectsInvalidRuleSourceObject(): void
    {
        $reflection = new ReflectionClass(RuleSetMetadata::class);

        $this->expectException(InvalidArgumentException::class);

        $reflection->newInstanceArgs([
            'XX_TEST',
            '1.0.0',
            [new \stdClass()],
            new DateTimeImmutable('2026-07-11'),
        ]);
    }
}
