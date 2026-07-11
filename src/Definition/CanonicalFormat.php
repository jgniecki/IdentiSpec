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
    private ?string $literalPrefix;

    /**
     * @param list<int> $lengths
     */
    public function __construct(
        string $description,
        private CharacterSet $characterSet,
        array $lengths,
        ?string $literalPrefix = null,
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

        if ($literalPrefix !== null) {
            $literalPrefix = trim($literalPrefix);

            if ($literalPrefix === '' || preg_match('/\A[A-Z0-9]+\z/', $literalPrefix) !== 1) {
                throw new InvalidArgumentException('Literal prefix must use uppercase ASCII letters or digits.');
            }

            if (
                $this->characterSet === CharacterSet::DIGITS
                && preg_match('/\A[0-9]+\z/', $literalPrefix) !== 1
            ) {
                throw new InvalidArgumentException('A DIGITS canonical format cannot declare a letter prefix.');
            }

            foreach ($uniqueLengths as $length) {
                if (strlen($literalPrefix) > $length) {
                    throw new InvalidArgumentException('Literal prefix cannot exceed a canonical length.');
                }
            }
        }

        /** @var non-empty-list<positive-int> $normalizedLengths */
        $normalizedLengths = $uniqueLengths;

        $this->description = $description;
        $this->lengths = $normalizedLengths;
        $this->literalPrefix = $literalPrefix;
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

    public function literalPrefix(): ?string
    {
        return $this->literalPrefix;
    }
}
