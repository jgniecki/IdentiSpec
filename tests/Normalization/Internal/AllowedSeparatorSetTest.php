<?php

declare(strict_types=1);

namespace IdentiSpec\Tests\Normalization\Internal;

use IdentiSpec\Normalization\Internal\AllowedSeparatorSet;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AllowedSeparatorSetTest extends TestCase
{
    #[DataProvider('safePrintableSeparators')]
    public function testAcceptsSafePrintableAscii(string $separator): void
    {
        self::assertTrue(AllowedSeparatorSet::forNumeric([$separator])->contains($separator));
    }

    /** @return iterable<string, array{string}> */
    public static function safePrintableSeparators(): iterable
    {
        yield 'space' => [' '];
        yield 'hyphen' => ['-'];
        yield 'slash' => ['/'];
        yield 'dot' => ['.'];
        yield 'tilde' => ['~'];
    }

    #[DataProvider('unsafeSeparators')]
    public function testRejectsControlAndNonAsciiSeparators(string $separator): void
    {
        $this->expectException(InvalidArgumentException::class);

        AllowedSeparatorSet::forNumeric([$separator]);
    }

    /** @return iterable<string, array{string}> */
    public static function unsafeSeparators(): iterable
    {
        yield 'nul' => ["\0"];
        yield 'tab' => ["\t"];
        yield 'line feed' => ["\n"];
        yield 'carriage return' => ["\r"];
        yield 'unit separator' => ["\x1F"];
        yield 'delete' => ["\x7F"];
        yield 'non ASCII byte' => ["\x80"];
        yield 'unicode' => ['Ł'];
    }

    public function testAlphanumericStillRejectsCanonicalLettersAndDigits(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AllowedSeparatorSet::forAlphanumeric(['A']);
    }
}
