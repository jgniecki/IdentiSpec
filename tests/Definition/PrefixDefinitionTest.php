<?php

declare(strict_types=1);

namespace IdentiSpec\Tests\Definition;

use IdentiSpec\Definition\CanonicalFormat;
use IdentiSpec\Definition\IdentifierDefinition;
use IdentiSpec\Definition\PrefixDefinition;
use IdentiSpec\Enum\CharacterSet;
use IdentiSpec\Enum\IdentifierCategory;
use IdentiSpec\Enum\PrefixPolicy;
use IdentiSpec\Enum\ValidationCapability;
use IdentiSpec\Enum\ValidationLevel;
use IdentiSpec\Tests\Fixture\TestIdentifierTypeValidator;
use IdentiSpec\Value\IdentifierKey;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PrefixDefinitionTest extends TestCase
{
    public function testNoneIsTheDefaultCanonicalPrefixPolicy(): void
    {
        $format = new CanonicalFormat('Ten digits.', CharacterSet::DIGITS, [10]);

        self::assertSame(PrefixPolicy::NONE, $format->prefix()->policy());
        self::assertNull($format->prefix()->literal());
        self::assertFalse($format->prefix()->includedInCanonicalValue());
    }

    public function testRequiredPrefixBelongsToCanonicalValue(): void
    {
        $format = new CanonicalFormat(
            'PL followed by ten digits.',
            CharacterSet::ASCII_ALPHANUMERIC,
            [12],
            PrefixDefinition::required('PL'),
        );

        self::assertSame(PrefixPolicy::REQUIRED, $format->prefix()->policy());
        self::assertSame('PL', $format->prefix()->literal());
        self::assertTrue($format->prefix()->includedInCanonicalValue());
    }

    public function testForbiddenLetterPrefixCanDescribeNumericIdentifierBody(): void
    {
        $format = new CanonicalFormat(
            'Ten-digit Polish NIP without a country prefix.',
            CharacterSet::DIGITS,
            [10],
            PrefixDefinition::forbidden('PL'),
        );

        self::assertSame(CharacterSet::DIGITS, $format->characterSet());
        self::assertSame(PrefixPolicy::FORBIDDEN, $format->prefix()->policy());
        self::assertSame('PL', $format->prefix()->literal());
        self::assertFalse($format->prefix()->includedInCanonicalValue());
    }

    public function testOptionalPrefixIsPresentationMetadataOutsideCanonicalValue(): void
    {
        $prefix = PrefixDefinition::optional('XX');

        self::assertSame(PrefixPolicy::OPTIONAL, $prefix->policy());
        self::assertSame('XX', $prefix->literal());
        self::assertFalse($prefix->includedInCanonicalValue());
    }

    #[DataProvider('missingLiteralPolicies')]
    public function testNonNonePolicyRequiresLiteral(PrefixPolicy $policy): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PrefixDefinition($policy);
    }

    /** @return iterable<string, array{PrefixPolicy}> */
    public static function missingLiteralPolicies(): iterable
    {
        yield 'required' => [PrefixPolicy::REQUIRED];
        yield 'optional' => [PrefixPolicy::OPTIONAL];
        yield 'forbidden' => [PrefixPolicy::FORBIDDEN];
    }

    public function testNoneRejectsLiteral(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PrefixDefinition(PrefixPolicy::NONE, 'PL');
    }

    public function testNoneRejectsCanonicalInclusion(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PrefixDefinition(PrefixPolicy::NONE, null, true);
    }

    public function testRequiredRejectsMissingCanonicalInclusion(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PrefixDefinition(PrefixPolicy::REQUIRED, 'PL');
    }

    #[DataProvider('nonCanonicalPolicies')]
    public function testNonRequiredPolicyRejectsCanonicalInclusion(PrefixPolicy $policy): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PrefixDefinition($policy, 'PL', true);
    }

    /** @return iterable<string, array{PrefixPolicy}> */
    public static function nonCanonicalPolicies(): iterable
    {
        yield 'optional' => [PrefixPolicy::OPTIONAL];
        yield 'forbidden' => [PrefixPolicy::FORBIDDEN];
    }

    public function testRequiredLetterPrefixIsRejectedForDigitsCanonicalValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CanonicalFormat(
            'Invalid required prefix.',
            CharacterSet::DIGITS,
            [12],
            PrefixDefinition::required('PL'),
        );
    }

    public function testRequiredPrefixCannotExceedCanonicalLength(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CanonicalFormat(
            'Invalid short format.',
            CharacterSet::ASCII_ALPHANUMERIC,
            [1],
            PrefixDefinition::required('PL'),
        );
    }

    public function testNonNonePolicyRequiresPrefixCapability(): void
    {
        $base = TestIdentifierTypeValidator::definitionFor();
        $this->expectException(InvalidArgumentException::class);

        new IdentifierDefinition(
            IdentifierKey::fromParts('PL', 'NIP'),
            'Polish tax identification number',
            IdentifierCategory::TAX,
            new CanonicalFormat(
                'Ten digits without PL.',
                CharacterSet::DIGITS,
                [10],
                PrefixDefinition::forbidden('PL'),
            ),
            ValidationLevel::FORMAT_ONLY,
            [ValidationCapability::CHARACTERS, ValidationCapability::LENGTH],
            $base->metadata(),
        );
    }

    public function testPrefixCapabilityRequiresNonNonePolicy(): void
    {
        $base = TestIdentifierTypeValidator::definitionFor();
        $this->expectException(InvalidArgumentException::class);

        new IdentifierDefinition(
            IdentifierKey::fromParts('XX', 'NONE'),
            'Identifier without prefix rules',
            IdentifierCategory::CUSTOM,
            new CanonicalFormat('Two characters.', CharacterSet::ASCII_ALPHANUMERIC, [2]),
            ValidationLevel::FORMAT_ONLY,
            [
                ValidationCapability::CHARACTERS,
                ValidationCapability::LENGTH,
                ValidationCapability::PREFIX,
            ],
            $base->metadata(),
        );
    }

    public function testForbiddenPrefixDefinitionWithCapabilityIsValid(): void
    {
        $base = TestIdentifierTypeValidator::definitionFor();
        $definition = new IdentifierDefinition(
            IdentifierKey::fromParts('PL', 'NIP'),
            'Polish tax identification number',
            IdentifierCategory::TAX,
            new CanonicalFormat(
                'Ten digits without PL.',
                CharacterSet::DIGITS,
                [10],
                PrefixDefinition::forbidden('PL'),
            ),
            ValidationLevel::FORMAT_ONLY,
            [
                ValidationCapability::CHARACTERS,
                ValidationCapability::LENGTH,
                ValidationCapability::PREFIX,
            ],
            $base->metadata(),
        );

        self::assertTrue($definition->hasCapability(ValidationCapability::PREFIX));
        self::assertSame(PrefixPolicy::FORBIDDEN, $definition->canonicalFormat()->prefix()->policy());
    }

    public function testLegacyLiteralPrefixMapsToRequiredPolicy(): void
    {
        $format = new CanonicalFormat(
            'Legacy prefixed format.',
            CharacterSet::ASCII_ALPHANUMERIC,
            [4],
            'PL',
        );

        self::assertSame(PrefixPolicy::REQUIRED, $format->prefix()->policy());
        self::assertSame('PL', $format->literalPrefix());
    }
}
