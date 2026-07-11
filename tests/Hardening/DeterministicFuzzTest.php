<?php

declare(strict_types=1);

namespace IdentiSpec\Tests\Hardening;

use IdentiSpec\Contract\IdentifierNormalizer;
use IdentiSpec\Enum\LetterCasePolicy;
use IdentiSpec\Enum\ValidationMode;
use IdentiSpec\Normalization\AsciiAlphanumericNormalizer;
use IdentiSpec\Normalization\NormalizationResult;
use IdentiSpec\Normalization\NumericNormalizer;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class DeterministicFuzzTest extends TestCase
{
    /** @return iterable<string, array{IdentifierNormalizer}> */
    public static function normalizers(): iterable
    {
        yield 'numeric' => [new NumericNormalizer([' ', '-'])];
        yield 'alphanumeric preserve' => [new AsciiAlphanumericNormalizer([' ', '-'])];
        yield 'alphanumeric uppercase' => [
            new AsciiAlphanumericNormalizer([' ', '-'], LetterCasePolicy::UPPERCASE),
        ];
        yield 'alphanumeric lowercase' => [
            new AsciiAlphanumericNormalizer([' ', '-'], LetterCasePolicy::LOWERCASE),
        ];
    }

    #[DataProvider('normalizers')]
    public function testGeneratedInputsAreHandledDeterministically(
        IdentifierNormalizer $normalizer,
    ): void {
        foreach (self::corpus() as $value) {
            foreach (ValidationMode::cases() as $mode) {
                $first = $normalizer->normalize($value, $mode);
                $second = $normalizer->normalize($value, $mode);

                self::assertSame(self::snapshot($first), self::snapshot($second));

                if (!$first->isSuccessful()) {
                    self::assertNull($first->normalizedValue());
                    self::assertNotSame([], $first->issues());
                }
            }
        }
    }

    /** @return iterable<string> */
    private static function corpus(): iterable
    {
        yield '';
        yield "\0\x01\x1F\x7F";
        yield "\xC5\x81\xF0\x9F\x92\xA5";
        yield str_repeat('9', 16_384);
        yield str_repeat('a-A 9', 2_048);

        $state = 0x1D3E5A77;

        for ($case = 0; $case < 1_000; ++$case) {
            $state = self::nextState($state);
            $length = $state % 257;
            $value = '';

            for ($position = 0; $position < $length; ++$position) {
                $state = self::nextState($state);
                $value .= chr($state & 0xFF);
            }

            yield $value;
        }
    }

    private static function nextState(int $state): int
    {
        return (int) (($state * 1_103_515_245 + 12_345) & 0x7FFFFFFF);
    }

    /**
     * @return array{
     *     successful: bool,
     *     normalized: string|null,
     *     issues: list<array{string, int|null, array<string, scalar|null>}>,
     *     transformations: list<array{string, int, array<string, scalar|null>}>
     * }
     */
    private static function snapshot(NormalizationResult $result): array
    {
        return [
            'successful' => $result->isSuccessful(),
            'normalized' => $result->normalizedValue(),
            'issues' => array_map(
                static fn($issue): array => [
                    $issue->code()->value(),
                    $issue->position(),
                    $issue->context(),
                ],
                $result->issues(),
            ),
            'transformations' => array_map(
                static fn($transformation): array => [
                    $transformation->code()->value(),
                    $transformation->position(),
                    $transformation->context(),
                ],
                $result->transformations(),
            ),
        ];
    }
}
