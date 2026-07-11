<?php

declare(strict_types=1);

namespace IdentiSpec\Checksum\PL;

use IdentiSpec\Checksum\WeightedModulo;
use InvalidArgumentException;
use SensitiveParameter;

final readonly class NipChecksum
{
    private const LENGTH = 10;

    /** @var list<int> */
    private const WEIGHTS = [6, 5, 7, 2, 3, 4, 5, 6, 7];

    private WeightedModulo $algorithm;

    public function __construct()
    {
        $this->algorithm = new WeightedModulo(self::WEIGHTS, 11);
    }

    public function isValid(
        #[SensitiveParameter]
        string $value,
    ): bool {
        if (strlen($value) !== self::LENGTH || preg_match('/\A[0-9]{10}\z/D', $value) !== 1) {
            throw new InvalidArgumentException('NIP checksum requires exactly ten ASCII digits.');
        }

        $expectedCheckDigit = $this->algorithm->calculate(substr($value, 0, 9));
        $actualCheckDigit = (int) $value[9];

        return $expectedCheckDigit !== 10 && $expectedCheckDigit === $actualCheckDigit;
    }
}
