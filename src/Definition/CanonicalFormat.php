<?php

declare(strict_types=1);

namespace IdentiSpec\Definition;

use IdentiSpec\Enum\CharacterSet;
use InvalidArgumentException;

final readonly class CanonicalFormat
{
    /** @var non-empty-list<positive-int> */
    private array $lengths;
    private string $description;
    private PrefixDefinition $prefix;

    /**
     * @param list<int> $lengths
     */
    public function __construct(
        string $description,
        private CharacterSet $characterSet,
        array $lengths,
        ?PrefixDefinition $prefix = null,
    ) {
        $description = trim($description);

        if ($description === '') {
            throw new InvalidArgumentException('Canonical format description cannot be empty.');
        }

        if ($lengths === []) {
            throw new InvalidArgumentException('Canonical format must declare at least one length.');
        }

        $uniqueLengths = [];

        foreach ($lengths as $length) {
            if ($length < 1) {
                throw new InvalidArgumentException('Canonical lengths must be positive integers.');
            }

            $uniqueLengths[$length] = $length;
        }

        sort($uniqueLengths);
        $prefix ??= PrefixDefinition::none();

        if ($prefix->includedInCanonicalValue()) {
            $literal = $prefix->literal();

            if ($literal === null) {
                throw new InvalidArgumentException('A canonical prefix requires a literal value.');
            }

            if (
                $this->characterSet === CharacterSet::DIGITS
                && preg_match('/\A[0-9]+\z/', $literal) !== 1
            ) {
                throw new InvalidArgumentException('A DIGITS canonical format cannot include a letter prefix.');
            }

            foreach ($uniqueLengths as $length) {
                if (strlen($literal) > $length) {
                    throw new InvalidArgumentException('Literal prefix cannot exceed a canonical length.');
                }
            }
        }

        /** @var non-empty-list<positive-int> $normalizedLengths */
        $normalizedLengths = $uniqueLengths;

        $this->description = $description;
        $this->lengths = $normalizedLengths;
        $this->prefix = $prefix;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function characterSet(): CharacterSet
    {
        return $this->characterSet;
    }

    /** @return non-empty-list<positive-int> */
    public function lengths(): array
    {
        return $this->lengths;
    }

    public function prefix(): PrefixDefinition
    {
        return $this->prefix;
    }
}
