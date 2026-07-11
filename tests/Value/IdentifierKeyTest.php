<?php

declare(strict_types=1);

namespace IdentiSpec\Tests\Value;

use IdentiSpec\Exception\InvalidIdentifierKey;
use IdentiSpec\Value\IdentifierKey;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IdentifierKeyTest extends TestCase
{
    public function testCanonicalizesParts(): void
    {
        $key = IdentifierKey::fromParts(' pl ', ' vat_eu ');

        self::assertSame('PL', $key->jurisdictionCode());
        self::assertSame('VAT_EU', $key->identifierType());
        self::assertSame('PL:VAT_EU', $key->toString());
        self::assertSame('PL:VAT_EU', (string) $key);
        self::assertTrue($key->equals(IdentifierKey::fromParts('PL', 'VAT_EU')));
    }

    #[DataProvider('invalidParts')]
    public function testRejectsInvalidParts(string $jurisdictionCode, string $identifierType): void
    {
        $this->expectException(InvalidIdentifierKey::class);

        IdentifierKey::fromParts($jurisdictionCode, $identifierType);
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidParts(): iterable
    {
        yield 'empty jurisdiction' => ['', 'NIP'];
        yield 'empty type' => ['PL', ''];
        yield 'leading digit' => ['1PL', 'NIP'];
        yield 'space inside' => ['PL', 'VAT EU'];
        yield 'non ASCII' => ['PŁ', 'NIP'];
    }
}
