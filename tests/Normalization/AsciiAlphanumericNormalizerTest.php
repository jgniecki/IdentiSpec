<?php

declare(strict_types=1);

namespace IdentiSpec\Tests\Normalization;

use IdentiSpec\Diagnostic\DiagnosticCode;
use IdentiSpec\Enum\LetterCasePolicy;
use IdentiSpec\Enum\ValidationMode;
use IdentiSpec\Normalization\AsciiAlphanumericNormalizer;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AsciiAlphanumericNormalizerTest extends TestCase
{
    #[DataProvider('preservedValues')]
    public function testPreserveAcceptsAsciiLettersAndDigits(string $value): void
    {
        $result = (new AsciiAlphanumericNormalizer())->normalize($value, ValidationMode::STRICT);

        self::assertTrue($result->isSuccessful());
        self::assertSame($value, $result->normalizedValue());
        self::assertSame([], $result->transformations());
    }

    /** @return iterable<string, array{string}> */
    public static function preservedValues(): iterable
    {
        yield 'uppercase' => ['ABC123'];
        yield 'lowercase' => ['abc123'];
        yield 'mixed' => ['AbC123'];
        yield 'ASCII boundaries' => ['09AZaz'];
    }

    public function testStrictUppercaseRejectsLowercase(): void
    {
        $result = (new AsciiAlphanumericNormalizer(
            letterCasePolicy: LetterCasePolicy::UPPERCASE,
        ))->normalize('ABc12', ValidationMode::STRICT);

        self::assertFalse($result->isSuccessful());
        self::assertNull($result->normalizedValue());
        self::assertSame(2, $result->issues()[0]->position());
        self::assertTrue($result->issues()[0]->code()->equals(DiagnosticCode::invalidCharacterCase()));
        self::assertSame(
            ['actual' => 'c', 'expected_case' => 'UPPERCASE'],
            $result->issues()[0]->context(),
        );
    }

    public function testStrictLowercaseRejectsUppercase(): void
    {
        $result = (new AsciiAlphanumericNormalizer(
            letterCasePolicy: LetterCasePolicy::LOWERCASE,
        ))->normalize('abC12', ValidationMode::STRICT);

        self::assertFalse($result->isSuccessful());
        self::assertSame(2, $result->issues()[0]->position());
        self::assertTrue($result->issues()[0]->code()->equals(DiagnosticCode::invalidCharacterCase()));
    }

    public function testLenientUppercaseRecordsCaseAndSeparatorTransformations(): void
    {
        $result = (new AsciiAlphanumericNormalizer(
            ['-', ' '],
            LetterCasePolicy::UPPERCASE,
        ))->normalize('aB- c1', ValidationMode::LENIENT);

        self::assertTrue($result->isSuccessful());
        self::assertSame('ABC1', $result->normalizedValue());
        self::assertSame(
            [
                'NORMALIZED_CHARACTER_CASE@0',
                'REMOVED_SEPARATOR@2',
                'REMOVED_SEPARATOR@3',
                'NORMALIZED_CHARACTER_CASE@4',
            ],
            array_map(
                static fn($transformation): string => sprintf(
                    '%s@%d',
                    $transformation->code()->value(),
                    $transformation->position(),
                ),
                $result->transformations(),
            ),
        );
        self::assertSame(['separator' => '-'], $result->transformations()[1]->context());
        self::assertSame(['separator' => 'SPACE'], $result->transformations()[2]->context());
    }

    public function testLenientLowercaseConvertsEveryUppercaseLetter(): void
    {
        $result = (new AsciiAlphanumericNormalizer(
            letterCasePolicy: LetterCasePolicy::LOWERCASE,
        ))->normalize('AB12', ValidationMode::LENIENT);

        self::assertSame('ab12', $result->normalizedValue());
        self::assertCount(2, $result->transformations());
        self::assertSame(['A', 'a'], array_values($result->transformations()[0]->context()));
    }

    #[DataProvider('invalidValues')]
    public function testRejectsNonAsciiAndUnknownCharacters(string $value, int $position): void
    {
        $result = (new AsciiAlphanumericNormalizer(['-']))->normalize($value, ValidationMode::LENIENT);

        self::assertFalse($result->isSuccessful());
        self::assertNull($result->normalizedValue());
        self::assertSame($position, $result->issues()[0]->position());
        self::assertTrue($result->issues()[0]->code()->equals(DiagnosticCode::invalidCharacter()));
    }

    /** @return iterable<string, array{string, int}> */
    public static function invalidValues(): iterable
    {
        yield 'unicode' => ['ABŁ12', 2];
        yield 'symbol' => ['AB/12', 2];
        yield 'control' => ["AB\x0012", 2];
    }

    public function testFailureKeepsEarlierTransformationsWithoutPartialValue(): void
    {
        $result = (new AsciiAlphanumericNormalizer(
            ['-'],
            LetterCasePolicy::UPPERCASE,
        ))->normalize('a-/', ValidationMode::LENIENT);

        self::assertFalse($result->isSuccessful());
        self::assertNull($result->normalizedValue());
        self::assertCount(2, $result->transformations());
        self::assertSame(2, $result->issues()[0]->position());
    }

    public function testEmptyAndSeparatorOnlyInputsAreEmpty(): void
    {
        $normalizer = new AsciiAlphanumericNormalizer(['-']);

        $empty = $normalizer->normalize('', ValidationMode::STRICT);
        $separatorOnly = $normalizer->normalize('--', ValidationMode::LENIENT);

        self::assertTrue($empty->issues()[0]->code()->equals(DiagnosticCode::emptyValue()));
        self::assertTrue($separatorOnly->issues()[0]->code()->equals(DiagnosticCode::emptyValue()));
        self::assertCount(2, $separatorOnly->transformations());
    }

    public function testRejectsCanonicalCharacterAsSeparator(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AsciiAlphanumericNormalizer(['A']);
    }

    #[DataProvider('canonicalSeparators')]
    public function testRejectsEveryCanonicalBoundaryAsSeparator(string $separator): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AsciiAlphanumericNormalizer([$separator]);
    }

    /** @return iterable<array{string}> */
    public static function canonicalSeparators(): iterable
    {
        yield ['0'];
        yield ['9'];
        yield ['A'];
        yield ['Z'];
        yield ['a'];
        yield ['z'];
    }

    #[DataProvider('printableCharacters')]
    public function testDiagnosticCharactersHaveStableRepresentations(string $character, string $expected): void
    {
        $result = (new AsciiAlphanumericNormalizer())->normalize('A' . $character, ValidationMode::STRICT);

        self::assertSame(['character' => $expected], $result->issues()[0]->context());
    }

    /** @return iterable<string, array{string, string}> */
    public static function printableCharacters(): iterable
    {
        yield 'space' => [' ', 'SPACE'];
        yield 'tab' => ["\t", 'TAB'];
        yield 'line feed' => ["\n", 'LF'];
        yield 'carriage return' => ["\r", 'CR'];
        yield 'printable symbol' => ['/', '/'];
        yield 'non-printable byte' => ["\x7F", '0x7F'];
    }

    public function testHandlesLargeValueDeterministically(): void
    {
        $value = str_repeat('Ab12-', 2_000);
        $normalizer = new AsciiAlphanumericNormalizer(['-'], LetterCasePolicy::UPPERCASE);

        $first = $normalizer->normalize($value, ValidationMode::LENIENT);
        $second = $normalizer->normalize($value, ValidationMode::LENIENT);

        self::assertTrue($first->isSuccessful());
        self::assertSame(8_000, strlen((string) $first->normalizedValue()));
        self::assertEquals($first, $second);
    }
}
