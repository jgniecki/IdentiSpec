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
use IdentiSpec\Validator\PL\VatEuValidator;

final class VatEuValidatorTest extends IdentifierTypeValidatorContractTestCase
{
    protected function validator(): IdentifierTypeValidator
    {
        return new VatEuValidator();
    }

    protected function validExamples(): iterable
    {
        yield 'checksum-valid example 1' => 'PL5260250995';
        yield 'checksum-valid example 2' => 'PL1234563218';
        yield 'checksum-valid example 3' => 'PL8567346215';
    }

    protected function invalidExamples(): iterable
    {
        yield 'empty' => '';
        yield 'missing prefix' => '5260250995';
        yield 'wrong prefix' => 'DE5260250995';
        yield 'too short' => 'PL526025099';
        yield 'too long' => 'PL52602509955';
        yield 'wrong checksum' => 'PL5260250994';
        yield 'letter in numeric body' => 'PL52602A0995';
        yield 'strict separator' => 'PL 526-025-09-95';
        yield 'strict lowercase prefix' => 'pl5260250995';
    }

    public function testDefinitionDeclaresVatEuContract(): void
    {
        $definition = (new VatEuValidator())->definition();

        self::assertSame('PL:VAT_EU', $definition->key()->toString());
        self::assertSame(ValidationLevel::FORMAT_AND_CHECKSUM, $definition->validationLevel());
        self::assertSame([12], $definition->canonicalFormat()->lengths());
        self::assertSame(PrefixPolicy::REQUIRED, $definition->canonicalFormat()->prefix()->policy());
        self::assertSame('PL', $definition->canonicalFormat()->prefix()->literal());
        self::assertTrue($definition->canonicalFormat()->prefix()->includedInCanonicalValue());
        self::assertTrue($definition->hasCapability(ValidationCapability::PREFIX));
        self::assertTrue($definition->hasCapability(ValidationCapability::CHECKSUM));
        self::assertSame('PL_VAT_EU', $definition->metadata()->ruleSetId());
        self::assertSame('1.0.0', $definition->metadata()->version());
    }

    public function testMissingOrWrongPrefixIsRejectedBeforeNormalization(): void
    {
        foreach (['5260250995', 'DE5260250995', 'P 5260250995'] as $value) {
            $result = (new VatEuValidator())->validate(
                $value,
                new ValidationOptions(ValidationMode::LENIENT),
            );

            self::assertSame(ValidationStatus::INVALID, $result->status());
            self::assertNull($result->normalizedValue());
            self::assertTrue($result->issues()[0]->code()->equals(DiagnosticCode::invalidPrefix()));
            self::assertSame(0, $result->issues()[0]->position());
            self::assertSame([], $result->transformations());
        }
    }

    public function testLenientModeNormalizesPrefixCaseAndApprovedSeparators(): void
    {
        $result = (new VatEuValidator())->validate(
            'pl 526-025-09-95',
            new ValidationOptions(ValidationMode::LENIENT),
        );

        self::assertSame(ValidationStatus::VALID, $result->status());
        self::assertSame('PL5260250995', $result->normalizedValue());
        self::assertCount(6, $result->transformations());
        self::assertSame(
            [
                'NORMALIZED_CHARACTER_CASE',
                'NORMALIZED_CHARACTER_CASE',
                'REMOVED_SEPARATOR',
                'REMOVED_SEPARATOR',
                'REMOVED_SEPARATOR',
                'REMOVED_SEPARATOR',
            ],
            array_map(
                static fn($transformation): string => $transformation->code()->value(),
                $result->transformations(),
            ),
        );
        $result->assertConsistentWith((new VatEuValidator())->definition());
    }

    public function testStrictModeRejectsLowercasePrefixCase(): void
    {
        $result = (new VatEuValidator())->validate(
            'pl5260250995',
            new ValidationOptions(ValidationMode::STRICT),
        );

        self::assertSame(ValidationStatus::INVALID, $result->status());
        self::assertNull($result->normalizedValue());
        self::assertTrue($result->issues()[0]->code()->equals(DiagnosticCode::invalidCharacterCase()));
        self::assertSame(0, $result->issues()[0]->position());
    }

    public function testRejectsLettersInNumericBody(): void
    {
        $result = (new VatEuValidator())->validate(
            'PL52602A0995',
            new ValidationOptions(),
        );

        self::assertSame(ValidationStatus::INVALID, $result->status());
        self::assertSame('PL52602A0995', $result->normalizedValue());
        self::assertTrue($result->issues()[0]->code()->equals(DiagnosticCode::invalidEmbeddedValue()));
        self::assertSame(7, $result->issues()[0]->position());
    }

    public function testReportsChecksumAgainstNumericBody(): void
    {
        $result = (new VatEuValidator())->validate(
            'PL5260250994',
            new ValidationOptions(),
        );

        self::assertSame(ValidationStatus::INVALID, $result->status());
        self::assertSame('PL5260250994', $result->normalizedValue());
        self::assertTrue($result->issues()[0]->code()->equals(DiagnosticCode::invalidChecksum()));
        self::assertSame(11, $result->issues()[0]->position());
    }
}
