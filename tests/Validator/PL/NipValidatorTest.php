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

    protected function validExamples(): iterable
    {
        yield 'Ministry example' => '5260250995';
        yield 'checksum example 2' => '1234563218';
        yield 'checksum example 3' => '8567346215';
        yield 'leading one example' => '1000000006';
    }

    protected function invalidExamples(): iterable
    {
        yield 'empty' => '';
        yield 'too short' => '526025099';
        yield 'too long' => '52602509955';
        yield 'invalid checksum' => '5260250994';
        yield 'modulo eleven remainder ten' => '1234567890';
        yield 'forbidden uppercase prefix' => 'PL5260250995';
        yield 'forbidden lowercase prefix' => 'pl5260250995';
        yield 'letter in body' => '52602A0995';
        yield 'strict separator' => '526-025-09-95';
    }

    public function testDefinitionDeclaresNipContract(): void
    {
        $definition = (new NipValidator())->definition();

        self::assertSame('PL:NIP', $definition->key()->toString());
        self::assertSame(ValidationLevel::FORMAT_AND_CHECKSUM, $definition->validationLevel());
        self::assertSame([10], $definition->canonicalFormat()->lengths());
        self::assertSame(PrefixPolicy::FORBIDDEN, $definition->canonicalFormat()->prefix()->policy());
        self::assertSame('PL', $definition->canonicalFormat()->prefix()->literal());
        self::assertTrue($definition->hasCapability(ValidationCapability::CHARACTERS));
        self::assertTrue($definition->hasCapability(ValidationCapability::LENGTH));
        self::assertTrue($definition->hasCapability(ValidationCapability::PREFIX));
        self::assertTrue($definition->hasCapability(ValidationCapability::CHECKSUM));
        self::assertSame('PL_NIP', $definition->metadata()->ruleSetId());
        self::assertSame('1.0.0', $definition->metadata()->version());
    }

    #[DataProvider('prefixedValues')]
    public function testRejectsForbiddenCountryPrefixBeforeNormalization(string $value): void
    {
        $result = (new NipValidator())->validate($value, new ValidationOptions(ValidationMode::LENIENT));

        self::assertSame(ValidationStatus::INVALID, $result->status());
        self::assertNull($result->normalizedValue());
        self::assertTrue($result->issues()[0]->code()->equals(DiagnosticCode::invalidPrefix()));
        self::assertSame(0, $result->issues()[0]->position());
        self::assertSame([], $result->transformations());
    }

    /** @return iterable<string, array{string}> */
    public static function prefixedValues(): iterable
    {
        yield 'canonical casing' => ['PL5260250995'];
        yield 'lowercase' => ['pl5260250995'];
        yield 'prefix with separator' => ['PL 526-025-09-95'];
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
        self::assertSame(
            ['REMOVED_SEPARATOR', 'REMOVED_SEPARATOR', 'REMOVED_SEPARATOR'],
            array_map(
                static fn($transformation): string => $transformation->code()->value(),
                $result->transformations(),
            ),
        );
    }

    public function testStrictModeRejectsSeparatorWithoutReturningPartialValue(): void
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

    #[DataProvider('invalidLengths')]
    public function testReportsInvalidLength(string $value, int $actual): void
    {
        $result = (new NipValidator())->validate($value, new ValidationOptions());

        self::assertSame(ValidationStatus::INVALID, $result->status());
        self::assertSame($value, $result->normalizedValue());
        self::assertTrue($result->issues()[0]->code()->equals(DiagnosticCode::invalidLength()));
        self::assertSame(['expected' => 10, 'actual' => $actual], $result->issues()[0]->context());
    }

    /** @return iterable<string, array{string, int}> */
    public static function invalidLengths(): iterable
    {
        yield 'nine digits' => ['526025099', 9];
        yield 'eleven digits' => ['52602509955', 11];
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
        yield 'remainder ten cannot be represented' => ['1234567890'];
    }

    public function testSafeOutputDoesNotExposeNormalizedNip(): void
    {
        $value = '5260250995';
        $result = (new NipValidator())->validate($value, new ValidationOptions());
        $encoded = json_encode($result->toSafeArray(), JSON_THROW_ON_ERROR);

        self::assertStringNotContainsString($value, $encoded);
        self::assertStringContainsString('[REDACTED]', $encoded);
    }
}
