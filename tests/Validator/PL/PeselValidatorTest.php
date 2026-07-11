<?php

declare(strict_types=1);

namespace IdentiSpec\Tests\Validator\PL;

use IdentiSpec\Diagnostic\DiagnosticCode;
use IdentiSpec\Enum\IdentifierCategory;
use IdentiSpec\Enum\PrefixPolicy;
use IdentiSpec\Enum\ValidationCapability;
use IdentiSpec\Enum\ValidationLevel;
use IdentiSpec\Enum\ValidationMode;
use IdentiSpec\Enum\ValidationStatus;
use IdentiSpec\IdentifierInput;
use IdentiSpec\IdentifierValidator;
use IdentiSpec\Registry\ValidatorRegistry;
use IdentiSpec\Tests\Contract\IdentifierTypeValidatorContractTestCase;
use IdentiSpec\ValidationOptions;
use IdentiSpec\Validator\PL\NipValidator;
use IdentiSpec\Validator\PL\PeselValidator;
use PHPUnit\Framework\Attributes\DataProvider;

final class PeselValidatorTest extends IdentifierTypeValidatorContractTestCase
{
    protected function validator(): PeselValidator
    {
        return new PeselValidator();
    }

    protected function validExamples(): iterable
    {
        yield 'official Gov.pl example' => '02070803628';
        yield 'synthetic 1800 century' => self::completePayload('0081010000');
        yield 'synthetic 2200 century' => self::completePayload('0061010000');
    }

    protected function invalidExamples(): iterable
    {
        yield 'wrong checksum' => '02070803629';
        yield 'invalid embedded date' => self::completePayload('0222310000');
        yield 'too short' => '0207080362';
    }

    public function testDefinitionDescribesPeselContract(): void
    {
        $definition = $this->validator()->definition();

        self::assertSame('PL:PESEL', $definition->key()->toString());
        self::assertSame(IdentifierCategory::PERSONAL_ID, $definition->category());
        self::assertSame([11], $definition->canonicalFormat()->lengths());
        self::assertSame(PrefixPolicy::NONE, $definition->canonicalFormat()->prefix()->policy());
        self::assertSame(ValidationLevel::FULL_OFFLINE_RULES, $definition->validationLevel());
        self::assertTrue($definition->hasCapability(ValidationCapability::CHECKSUM));
        self::assertTrue($definition->hasCapability(ValidationCapability::EMBEDDED_SEMANTICS));
        self::assertSame('PL_PESEL', $definition->metadata()->ruleSetId());
        self::assertSame('1.0.0', $definition->metadata()->version());
    }

    #[DataProvider('validCenturyDates')]
    public function testAcceptsEveryEncodedCentury(string $payload): void
    {
        $result = $this->validator()->validate(self::completePayload($payload), new ValidationOptions());

        self::assertSame(ValidationStatus::VALID, $result->status());
    }

    /** @return iterable<string, array{string}> */
    public static function validCenturyDates(): iterable
    {
        yield '1800-01-01' => ['0081010000'];
        yield '1900-01-01' => ['0001010000'];
        yield '2000-01-01' => ['0021010000'];
        yield '2100-01-01' => ['0041010000'];
        yield '2200-01-01' => ['0061010000'];
        yield '2000 leap day' => ['0022290000'];
    }

    #[DataProvider('invalidEmbeddedDates')]
    public function testRejectsInvalidEmbeddedDate(string $payload): void
    {
        $result = $this->validator()->validate(self::completePayload($payload), new ValidationOptions());

        self::assertSame(ValidationStatus::INVALID, $result->status());
        self::assertSame(0, $result->issues()[0]->position());
        self::assertTrue($result->issues()[0]->code()->equals(DiagnosticCode::invalidEmbeddedValue()));
        self::assertSame([], $result->issues()[0]->context());
    }

    /** @return iterable<string, array{string}> */
    public static function invalidEmbeddedDates(): iterable
    {
        yield '1900 is not leap' => ['0002290000'];
        yield '2100 is not leap' => ['0042290000'];
        yield '2200 is not leap' => ['0062290000'];
        yield 'February 31' => ['0002310000'];
        yield 'month zero encoding' => ['0000010000'];
        yield 'month 13 encoding' => ['0013010000'];
        yield 'unsupported century encoding' => ['0073010000'];
        yield 'day zero' => ['0001000000'];
    }

    #[DataProvider('nonCanonicalInputs')]
    public function testBothModesRejectNonCanonicalInput(string $value): void
    {
        foreach (ValidationMode::cases() as $mode) {
            $result = $this->validator()->validate($value, new ValidationOptions($mode));

            self::assertSame(ValidationStatus::INVALID, $result->status());
            self::assertSame([], $result->transformations());
        }
    }

    /** @return iterable<string, array{string}> */
    public static function nonCanonicalInputs(): iterable
    {
        yield 'spaces' => ['020 708 036 28'];
        yield 'hyphens' => ['020708-03628'];
        yield 'unicode digit' => ["0207080362\xEF\xBC\x98"];
        yield 'control character' => ["020708\0" . '03628'];
    }

    public function testRejectsEverySingleDigitMutationOfOfficialExample(): void
    {
        $valid = '02070803628';

        for ($position = 0; $position < strlen($valid); ++$position) {
            $mutated = $valid;
            $mutated[$position] = (string) (((int) $valid[$position] + 1) % 10);
            $result = $this->validator()->validate($mutated, new ValidationOptions());

            self::assertSame(ValidationStatus::INVALID, $result->status(), 'Mutation at byte ' . $position);
        }
    }

    public function testChecksumFailurePointsAtFinalDigit(): void
    {
        $result = $this->validator()->validate('02070803629', new ValidationOptions());

        self::assertSame(ValidationStatus::INVALID, $result->status());
        self::assertSame(10, $result->issues()[0]->position());
        self::assertTrue($result->issues()[0]->code()->equals(DiagnosticCode::invalidChecksum()));
        self::assertSame([], $result->issues()[0]->context());
    }

    public function testSafeResultDoesNotExposePeselOrDerivedPersonalData(): void
    {
        $value = '02070803628';
        $result = $this->validator()->validate($value, new ValidationOptions());
        $encoded = json_encode($result->toSafeArray(), JSON_THROW_ON_ERROR);

        self::assertStringNotContainsString($value, $encoded);
        self::assertStringNotContainsString('2002-07-08', $encoded);
        self::assertStringNotContainsString('gender', strtolower($encoded));
        self::assertStringNotContainsString('birth', strtolower($encoded));
        self::assertStringContainsString('[REDACTED]', $encoded);
    }

    public function testFacadeRoutesAndListsNipAndPeselIndependently(): void
    {
        $registry = new ValidatorRegistry([new PeselValidator(), new NipValidator()]);
        $facade = new IdentifierValidator($registry);

        self::assertSame(
            ['PL:NIP', 'PL:PESEL'],
            array_map(static fn($definition): string => $definition->key()->toString(), $registry->definitions()),
        );
        self::assertSame(
            ValidationStatus::VALID,
            $facade->validate(new IdentifierInput('PL', 'PESEL', '02070803628'))->status(),
        );
    }

    private static function completePayload(string $payload): string
    {
        self::assertSame(10, strlen($payload));
        $weights = [1, 3, 7, 9, 1, 3, 7, 9, 1, 3];
        $sum = 0;

        foreach ($weights as $position => $weight) {
            $sum += ((int) $payload[$position]) * $weight;
        }

        return $payload . ((10 - ($sum % 10)) % 10);
    }
}
