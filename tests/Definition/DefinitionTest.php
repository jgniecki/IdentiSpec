<?php

declare(strict_types=1);

namespace IdentiSpec\Tests\Definition;

use DateTimeImmutable;
use IdentiSpec\Definition\CanonicalFormat;
use IdentiSpec\Definition\IdentifierDefinition;
use IdentiSpec\Definition\RuleSetMetadata;
use IdentiSpec\Definition\RuleSource;
use IdentiSpec\Enum\CharacterSet;
use IdentiSpec\Enum\ValidationCapability;
use IdentiSpec\Tests\Fixture\TestIdentifierTypeValidator;
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
    }

    public function testDefinitionDeduplicatesCapabilities(): void
    {
        $base = TestIdentifierTypeValidator::definitionFor();
        $definition = new IdentifierDefinition(
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

        self::assertSame(
            [ValidationCapability::CHARACTERS, ValidationCapability::LENGTH],
            $definition->capabilities(),
        );
    }

    public function testDefinitionDoesNotExposeAlgorithmSelector(): void
    {
        $properties = array_map(
            static fn (\ReflectionProperty $property): string => $property->getName(),
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
}
