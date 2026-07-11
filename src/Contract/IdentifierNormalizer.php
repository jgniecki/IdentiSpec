<?php

declare(strict_types=1);

namespace IdentiSpec\Contract;

use IdentiSpec\Enum\ValidationMode;
use IdentiSpec\Normalization\NormalizationResult;

interface IdentifierNormalizer
{
    public function normalize(string $value, ValidationMode $mode): NormalizationResult;
}
