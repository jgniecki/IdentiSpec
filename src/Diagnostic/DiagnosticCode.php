<?php

declare(strict_types=1);

namespace IdentiSpec\Diagnostic;

use InvalidArgumentException;

final readonly class DiagnosticCode
{
    private const PATTERN = '/\A[A-Z][A-Z0-9]*(?:_[A-Z0-9]+)*\z/D';

    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        if (preg_match(self::PATTERN, $value) !== 1) {
            throw new InvalidArgumentException('Diagnostic code must use UPPER_SNAKE_CASE.');
        }

        return new self($value);
    }

    public static function emptyValue(): self
    {
        return self::fromString('EMPTY_VALUE');
    }

    public static function invalidCharacter(): self
    {
        return self::fromString('INVALID_CHARACTER');
    }

    public static function invalidCharacterCase(): self
    {
        return self::fromString('INVALID_CHARACTER_CASE');
    }

    public static function invalidLength(): self
    {
        return self::fromString('INVALID_LENGTH');
    }

    public static function invalidPrefix(): self
    {
        return self::fromString('INVALID_PREFIX');
    }

    public static function invalidFormat(): self
    {
        return self::fromString('INVALID_FORMAT');
    }

    public static function invalidStructure(): self
    {
        return self::fromString('INVALID_STRUCTURE');
    }

    public static function invalidSegment(): self
    {
        return self::fromString('INVALID_SEGMENT');
    }

    public static function invalidChecksum(): self
    {
        return self::fromString('INVALID_CHECKSUM');
    }

    public static function invalidEmbeddedValue(): self
    {
        return self::fromString('INVALID_EMBEDDED_VALUE');
    }

    public static function normalizationNotAllowed(): self
    {
        return self::fromString('NORMALIZATION_NOT_ALLOWED');
    }

    public static function unsupportedIdentifier(): self
    {
        return self::fromString('UNSUPPORTED_IDENTIFIER');
    }

    public static function removedSeparator(): self
    {
        return self::fromString('REMOVED_SEPARATOR');
    }

    public static function normalizedCharacterCase(): self
    {
        return self::fromString('NORMALIZED_CHARACTER_CASE');
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
