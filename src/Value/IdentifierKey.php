<?php

declare(strict_types=1);

namespace IdentiSpec\Value;

use IdentiSpec\Exception\InvalidIdentifierKey;

final readonly class IdentifierKey
{
    private const PART_PATTERN = '/\A[A-Z][A-Z0-9_-]*\z/D';

    private string $jurisdictionCode;
    private string $identifierType;

    private function __construct(string $jurisdictionCode, string $identifierType)
    {
        $this->jurisdictionCode = $jurisdictionCode;
        $this->identifierType = $identifierType;
    }

    public static function fromParts(string $jurisdictionCode, string $identifierType): self
    {
        $jurisdictionCode = strtoupper(trim($jurisdictionCode));
        $identifierType = strtoupper(trim($identifierType));

        if (preg_match(self::PART_PATTERN, $jurisdictionCode) !== 1) {
            throw InvalidIdentifierKey::forPart('jurisdictionCode');
        }

        if (preg_match(self::PART_PATTERN, $identifierType) !== 1) {
            throw InvalidIdentifierKey::forPart('identifierType');
        }

        return new self($jurisdictionCode, $identifierType);
    }

    public function jurisdictionCode(): string
    {
        return $this->jurisdictionCode;
    }

    public function identifierType(): string
    {
        return $this->identifierType;
    }

    public function equals(self $other): bool
    {
        return $this->jurisdictionCode === $other->jurisdictionCode
            && $this->identifierType === $other->identifierType;
    }

    public function toString(): string
    {
        return $this->jurisdictionCode . ':' . $this->identifierType;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
