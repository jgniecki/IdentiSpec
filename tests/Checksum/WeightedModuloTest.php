<?php

declare(strict_types=1);

namespace IdentiSpec\Tests\Checksum;

use IdentiSpec\Checksum\WeightedModulo;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WeightedModuloTest extends TestCase
{
    public function testCalculatesWeightedRemainderDeterministically(): void
    {
        $algorithm = new WeightedModulo([6, 5, 7, 2, 3, 4, 5, 6, 7], 11);

        self::assertSame(5, $algorithm->calculate('526025099'));
        self::assertSame(10, $algorithm->calculate('123456789'));
        self::assertSame(5, $algorithm->calculate('526025099'));
    }

    #[DataProvider('invalidConfigurations')]
    public function testRejectsInvalidConfiguration(array $weights, int $modulus): void
    {
        $this->expectException(InvalidArgumentException::class);

        new WeightedModulo($weights, $modulus);
    }

    /** @return iterable<string, array{list<int>, int}> */
    public static function invalidConfigurations(): iterable
    {
        yield 'no weights' => [[], 11];
        yield 'zero weight' => [[1, 0], 11];
        yield 'negative weight' => [[1, -1], 11];
        yield 'modulus one' => [[1], 1];
        yield 'negative modulus' => [[1], -1];
    }

    #[DataProvider('invalidPayloads')]
    public function testRejectsInvalidPayload(string $payload): void
    {
        $algorithm = new WeightedModulo([1, 2, 3], 11);
        $this->expectException(InvalidArgumentException::class);

        $algorithm->calculate($payload);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidPayloads(): iterable
    {
        yield 'too short' => ['12'];
        yield 'too long' => ['1234'];
        yield 'letter' => ['1A3'];
        yield 'unicode' => ['1Ł3'];
        yield 'separator' => ['1-3'];
    }
}
