<?php

declare(strict_types=1);

namespace IdentiSpec;

use IdentiSpec\Value\IdentifierKey;

final readonly class IdentifierInput
{
    private IdentifierKey $key;

    public function __construct(
        string $jurisdictionCode,
        string $identifierType,
        private string $value,
    ) {
        $this->key = IdentifierKey::fromParts($jurisdictionCode, $identifierType);
    }

    public function key(): IdentifierKey
    {
        return $this->key;
    }

    public function jurisdictionCode(): string
    {
        return $this->key->jurisdictionCode();
    }

    public function identifierType(): string
    {
        return $this->key->identifierType();
    }

    public function value(): string
    {
        return $this->value;
    }
}
