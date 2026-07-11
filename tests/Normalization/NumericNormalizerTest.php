<?php

declare(strict_types=1);

namespace IdentiSpec\Tests\Normalization;

use IdentiSpec\Diagnostic\DiagnosticCode;
use IdentiSpec\Enum\ValidationMode;
use IdentiSpec\Normalization\NumericNormalizer;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NumericNormalizerTest extends TestCase
{
    public function testStrictModeAcceptsCanonicalDigits(): void
    {
        $result = (new NumericNormalizer(['-', ' ']))->normalize('0123456789', ValidationMode::STRICT);

        self::assertTrue($result->isSuccessful());
        self::assertSame('0123456789', $result->normalizedValue());
        self::assertSame([], $result->transformations());
    }

    public function testLenientModeRecordsEveryAllowedSeparator(): void
    {
        $result = (new NumericNormalizer(['-', ' ']))->normalize('12- 34-5', ValidationMode::LENIENT);

        self::assertTrue($result->isSuccessful());
        self::assertSame('12345', $result->normalizedValue());
        self::assertSame([2, 3, 6], array_map(
            static fn($transformation): int => $transformation->position(),
            $result->transformations(),
        ));
        self::assertSame(
            ['-', 'SPACE', '-'],
            array_map(
                static fn($transformation): mixed => $transformation->context()['separator'],
                $result->transformations(),
            ),
        );
    }

    public function testStrictModeRejectsConfiguredSeparator(): void
    {
        $result = (new NumericNormalizer(['-']))->normalize('12-34', ValidationMode::STRICT);

        self::assertFalse($result->isSuccessful());
        self::assertNull($result->normalizedValue());
        self::assertSame(2, $result->issues()[0]->position());
        self::assertTrue($result->issues()[0]->code()->equals(DiagnosticCode::invalidCharacter()));
    }

    #[DataProvider('invalidValues')]
    public function testFailureNeverReturnsPartialNormalizedValue(string $value, int $position): void
    {
        $result = (new NumericNormalizer(['-']))->normalize($value, ValidationMode::LENIENT);

        self::assertFalse($result->isSuccessful());
        self::assertNull($result->normalizedValue());
        self::assertSame($position, $result->issues()[0]->position());
    }

    /** @return iterable<string, array{string, int}> */
    public static function invalidValues(): iterable
    {
        yield 'letter' => ['12A34', 2];
        yield 'unknown separator' => ['12/34', 2];
        yield 'unicode starts at byte offset' => ['12Ł34', 2];
    }

    public function testEmptyAndSeparatorOnlyInputsAreEmpty(): void
    {
        $normalizer = new NumericNormalizer(['-']);

        $empty = $normalizer->normalize('', ValidationMode::STRICT);
        $separatorOnly = $normalizer->normalize('--', ValidationMode::LENIENT);

        self::assertTrue($empty->issues()[0]->code()->equals(DiagnosticCode::emptyValue()));
        self::assertTrue($separatorOnly->issues()[0]->code()->equals(DiagnosticCode::emptyValue()));
        self::assertCount(2, $separatorOnly->transformations());
    }

    public function testRejectsInvalidSeparatorConfiguration(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new NumericNormalizer(['12']);
    }

    public function testIsDeterministic(): void
    {
        $normalizer = new NumericNormalizer(['-']);

        $first = $normalizer->normalize('12-34', ValidationMode::LENIENT);
        $second = $normalizer->normalize('12-34', ValidationMode::LENIENT);

        self::assertEquals($first, $second);
    }

    public function testSharedSeparatorInternalsDoNotChangeNumericBehavior(): void
    {
        $normalizer = new NumericNormalizer(['A']);
        $result = $normalizer->normalize('12A34', ValidationMode::LENIENT);

        self::assertTrue($result->isSuccessful());
        self::assertSame('1234', $result->normalizedValue());
        self::assertSame(2, $result->transformations()[0]->position());
    }
}
