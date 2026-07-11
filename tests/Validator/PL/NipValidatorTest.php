<?php

declare(strict_types=1);

namespace IdentiSpec\Tests\Validator\PL;

use IdentiSpec\Diagnostic\DiagnosticCode;
use IdentiSpec\Enum\PrefixPolicy;
use IdentiSpec\Enum\ValidationCapability;
use IdentiSpec\Enum\ValidationMode;
use IdentiSpec\Enum\ValidationStatus;
use IdentiSpec\IdentifierInput;
use IdentiSpec\IdentifierValidator;
use IdentiSpec\Registry\ValidatorRegistry;
use IdentiSpec\Tests\Contract\IdentifierTypeValidatorContractTestCase;
use IdentiSpec\ValidationOptions;
use IdentiSpec\Validator\PL\NipValidator;

final class NipValidatorTest extends IdentifierTypeValidatorContractTestCase
{
    protected function validator(): NipValidator
    {
        return new NipValidator();
    }

    protected function validExamples(): iterable
    {
        yield 'synthetic ascending and descending payload' => '1234563218';
        yield 'synthetic repeated payload' => '1111111111';
        yield 'synthetic reverse payload' => '9876543210';
    }

    protected function invalidExamples(): iterable
    {
        yield 'wrong checksum' => '1234563219';
        yield 'too short' => '123456321';
        yield 'forbidden prefix' => 'PL1234563218';
    }

    public function testDefinitionDescribesNipContract(): void
    {
        $definition = $this->validator()->definition();

        self::assertSame('PL:NIP', $definition->key()->toString());
        self::assertSame([10], $definition->canonicalFormat()->lengths());
        self::assertSame(PrefixPolicy::FORBIDDEN, $definition->canonicalFormat()->prefix()->policy());
        self::assertSame('PL', $definition->canonicalFormat()->prefix()->literal());
        self::assertTrue($definition->hasCapability(ValidationCapability::CHECKSUM));
        self::assertTrue($definition->hasCapability(ValidationCapability::PREFIX));
        self::assertSame('PL_NIP', $definition->metadata()->ruleSetId());
        self::assertSame('1.0.0', $definition->metadata()->version());
    }

    public function testStrictAcceptsOnlyCanonicalDigits(): void
    {
        $validator = $this->validator();

        foreach (['123-456-32-18', '123 456 32 18'] as $value) {
            $result = $validator->validate($value, new ValidationOptions());

            self::assertSame(ValidationStatus::INVALID, $result->status());
            self::assertTrue($result->issues()[0]->code()->equals(DiagnosticCode::invalidCharacter()));
        }
    }

    public function testLenientAcceptsOnlyDocumentedPresentationLayouts(): void
    {
        $validator = $this->validator();
        $options = new ValidationOptions(ValidationMode::LENIENT);

        foreach (['123-456-32-18', '123 456 32 18'] as $value) {
            $result = $validator->validate($value, $options);

            self::assertSame(ValidationStatus::VALID, $result->status());
            self::assertSame('1234563218', $result->normalizedValue());
            self::assertCount(3, $result->transformations());
        }

        foreach (['12-3456-32-18', '123-456 32-18', '-1234563218', '1234563218-'] as $value) {
            $result = $validator->validate($value, $options);

            self::assertSame(ValidationStatus::INVALID, $result->status());
            self::assertTrue(
                $result->issues()[0]->code()->equals(DiagnosticCode::normalizationNotAllowed()),
            );
        }
    }

    public function testPrefixIsRejectedWithoutSilentNormalization(): void
    {
        $result = $this->validator()->validate(
            'PL1234563218',
            new ValidationOptions(ValidationMode::LENIENT),
        );

        self::assertSame(ValidationStatus::INVALID, $result->status());
        self::assertNull($result->normalizedValue());
        self::assertSame(0, $result->issues()[0]->position());
        self::assertTrue($result->issues()[0]->code()->equals(DiagnosticCode::invalidPrefix()));
        self::assertSame([], $result->transformations());
    }

    public function testRemainderTenIsNeverAcceptedAsCheckDigit(): void
    {
        $result = $this->validator()->validate('1234567890', new ValidationOptions());

        self::assertSame(ValidationStatus::INVALID, $result->status());
        self::assertTrue($result->issues()[0]->code()->equals(DiagnosticCode::invalidChecksum()));
        self::assertSame(9, $result->issues()[0]->position());
    }

    public function testFacadeRoutesProductionValidator(): void
    {
        $facade = new IdentifierValidator(new ValidatorRegistry([$this->validator()]));
        $result = $facade->validate(new IdentifierInput('pl', 'nip', '1234563218'));

        self::assertSame(ValidationStatus::VALID, $result->status());
        self::assertSame('PL:NIP', $result->key()->toString());
    }
}
