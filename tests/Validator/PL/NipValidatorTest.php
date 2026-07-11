<?php

declare(strict_types=1);

namespace IdentiSpec\Tests\Validator\PL;

use IdentiSpec\Contract\IdentifierTypeValidator;
use IdentiSpec\Diagnostic\DiagnosticCode;
use IdentiSpec\Enum\PrefixPolicy;
use IdentiSpec\Enum\ValidationCapability;
use IdentiSpec\Enum\ValidationLevel;
use IdentiSpec\Enum\ValidationMode;
use IdentiSpec\Enum\ValidationStatus;
use IdentiSpec\Tests\Contract\IdentifierTypeValidatorContractTestCase;
use IdentiSpec\ValidationOptions;
use IdentiSpec\Validator\PL\NipValidator;
use PHPUnit\Framework\Attributes\DataProvider;

final class NipValidatorTest extends IdentifierTypeValidatorContractTestCase
{
    protected function validator(): IdentifierTypeValidator
    {
        return new NipValidator();
    }

    /** @return iterable<string> */
    protected function validExamples(): iterable
    {
        yield 'checksum-valid example 1' => '5260250995';
        yield 'checksum-valid example 2' => '1234563218';
        yield 'checksum-valid example 3' => '8567346215';
    }

    /** @return iterable<string> */
    protected function invalidExamples(): iterable
    {
        yield 'empty' => '';
        yield 'too short' => '526025099';
        yield 'too long' => '52602509955';
        yield 'wrong checksum' => '5260250994';
        yield 'remainder ten' => '1234567890';
        yield 'forbidden prefix' => 'PL5260250995';
        yield 'letter in body' => '52602A0995';
        yield 'strict separator' => '526-025-09-95';
    }

    public function testDefinitionDeclaresDomesticNipContract(): void
    {
        $definition = (new NipValidator())->definition();

        self::assertSame('PL:NIP', $definition->key()->toString());
        self::assertSame(ValidationLevel::FORMAT_AND_CHECKSUM, $definition->validationLevel());
        self::assertSame([10], $definition->canonicalFormat()->lengths());
        self::assertSame(PrefixPolicy::FORBIDDEN, $definition->canonicalFormat()->prefix()->policy());
        self::assertSame('PL', $definition->canonicalFormat()->prefix()->literal());
        self::assertTrue($definition->hasCapability(ValidationCapability::PREFIX));
        self::assertTrue($definition->hasCapability(ValidationCapability::CHECKSUM));
        self::assertSame('PL_NIP', $definition->metadata()->ruleSetId());
        self::assertSame('1.0.0', $definition->metadata()->version());
    }

    #[DataProvider('prefixedValues')]
    public function testRejectsCountryPrefixInEveryMode(string $value, ValidationMode $mode): void
    {
        $result = (new NipValidator())->validate($value, new ValidationOptions($mode));

        self::assertSame(ValidationStatus::INVALID, $result->status());
        self::assertNull($result->normalizedValue());
        self::assertTrue($result->issues()[0]->code()->equals(DiagnosticCode::invalidPrefix()));
        self::assertSame(0, $result->issues()[0]->position());
    }

    /** @return iterable<string, array{string, ValidationMode}> */
    public static function prefixedValues(): iterable
    {
        yield 'strict uppercase' => ['PL5260250995', ValidationMode::STRICT];
        yield 'lenient uppercase' => ['PL 526-025-09-95', ValidationMode::LENIENT];
        yield 'lenient lowercase' => ['pl5260250995', ValidationMode::LENIENT];
    }

    public function testLenientModeNormalizesApprovedSeparators(): void
    {
        $result = (new NipValidator())->validate(
            '526-025-09-95',
            new ValidationOptions(ValidationMode::LENIENT),
        );

        self::assertSame(ValidationStatus::VALID, $result->status());
        self::assertSame('5260250995', $result->normalizedValue());
        self::assertCount(3, $result->transformations());
        $result->assertConsistentWith((new NipValidator())->definition());
    }

    public function testStrictModeRejectsSeparatorWithoutPartialNormalizedValue(): void
    {
        $result = (new NipValidator())->validate(
            '526-025-09-95',
            new ValidationOptions(ValidationMode::STRICT),
        );

        self::assertSame(ValidationStatus::INVALID, $result->status());
        self::assertNull($result->normalizedValue());
        self::assertTrue($result->issues()[0]->code()->equals(DiagnosticCode::invalidCharacter()));
        self::assertSame(3, $result->issues()[0]->position());
    }

    #[DataProvider('invalidChecksums')]
    public function testReportsInvalidChecksum(string $value): void
    {
        $result = (new NipValidator())->validate($value, new ValidationOptions());

        self::assertSame(ValidationStatus::INVALID, $result->status());
        self::assertSame($value, $result->normalizedValue());
        self::assertTrue($result->issues()[0]->code()->equals(DiagnosticCode::invalidChecksum()));
        self::assertSame(9, $result->issues()[0]->position());
    }

    /** @return iterable<string, array{string}> */
    public static function invalidChecksums(): iterable
    {
        yield 'wrong check digit' => ['5260250994'];
        yield 'remainder ten' => ['1234567890'];
    }
}
