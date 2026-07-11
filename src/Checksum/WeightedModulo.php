<?php

declare(strict_types=1);

namespace IdentiSpec\Checksum;

use IdentiSpec\Contract\ChecksumAlgorithm;
use InvalidArgumentException;
use SensitiveParameter;

final readonly class WeightedModulo implements ChecksumAlgorithm
{
    /** @var non-empty-list<positive-int> */
    private array $weights;

    /**
     * @param list<int> $weights
     */
    public function __construct(array $weights, private int $modulus)
    {
        if ($weights === []) {
            throw new InvalidArgumentException('Weighted modulo requires at least one weight.');
        }

        foreach ($weights as $weight) {
            if ($weight < 1) {
                throw new InvalidArgumentException('Checksum weights must be positive integers.');
            }
        }

        if ($this->modulus < 2) {
            throw new InvalidArgumentException('Checksum modulus must be at least 2.');
        }

        /** @var non-empty-list<positive-int> $weights */
        $this->weights = $weights;
    }

    public function calculate(
        #[SensitiveParameter]
        string $payload,
    ): int {
        if (strlen($payload) !== count($this->weights)) {
            throw new InvalidArgumentException('Checksum payload length must match the number of weights.');
        }

        $sum = 0;

        foreach ($this->weights as $position => $weight) {
            $character = $payload[$position];

            if ($character < '0' || $character > '9') {
                throw new InvalidArgumentException('Weighted modulo accepts ASCII digits only.');
            }

            $sum += ((int) $character) * $weight;
        }

        return $sum % $this->modulus;
    }
}
