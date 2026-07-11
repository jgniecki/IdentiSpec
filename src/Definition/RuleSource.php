<?php

declare(strict_types=1);

namespace IdentiSpec\Definition;

use InvalidArgumentException;

final readonly class RuleSource
{
    private string $name;
    private string $reference;

    public function __construct(string $name, string $reference)
    {
        $name = trim($name);
        $reference = trim($reference);

        if ($name === '') {
            throw new InvalidArgumentException('Rule source name cannot be empty.');
        }

        if ($reference === '') {
            throw new InvalidArgumentException('Rule source reference cannot be empty.');
        }

        $this->name = $name;
        $this->reference = $reference;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function reference(): string
    {
        return $this->reference;
    }
}
