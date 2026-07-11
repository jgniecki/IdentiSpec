<?php

declare(strict_types=1);

namespace IdentiSpec\Tests\Diagnostic;

use IdentiSpec\Diagnostic\DiagnosticCode;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DiagnosticCodeTest extends TestCase
{
    public function testAcceptsExtensionCode(): void
    {
        $code = DiagnosticCode::fromString('ACME_CUSTOM_RULE');

        self::assertSame('ACME_CUSTOM_RULE', $code->value());
        self::assertTrue($code->equals(DiagnosticCode::fromString('ACME_CUSTOM_RULE')));
    }

    #[DataProvider('invalidCodes')]
    public function testRejectsInvalidCode(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        DiagnosticCode::fromString($value);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidCodes(): iterable
    {
        yield 'empty' => [''];
        yield 'lowercase' => ['invalid_format'];
        yield 'leading underscore' => ['_INVALID'];
        yield 'double underscore' => ['INVALID__FORMAT'];
        yield 'space' => ['INVALID FORMAT'];
    }

    public function testCoreAuthoringCodesAreStable(): void
    {
        self::assertSame('INVALID_CHARACTER_CASE', DiagnosticCode::invalidCharacterCase()->value());
        self::assertSame('INVALID_LENGTH', DiagnosticCode::invalidLength()->value());
        self::assertSame('INVALID_PREFIX', DiagnosticCode::invalidPrefix()->value());
        self::assertSame('INVALID_STRUCTURE', DiagnosticCode::invalidStructure()->value());
        self::assertSame('INVALID_SEGMENT', DiagnosticCode::invalidSegment()->value());
        self::assertSame('INVALID_CHECKSUM', DiagnosticCode::invalidChecksum()->value());
        self::assertSame('INVALID_EMBEDDED_VALUE', DiagnosticCode::invalidEmbeddedValue()->value());
        self::assertSame('NORMALIZATION_NOT_ALLOWED', DiagnosticCode::normalizationNotAllowed()->value());
        self::assertSame('NORMALIZED_CHARACTER_CASE', DiagnosticCode::normalizedCharacterCase()->value());
    }

}
