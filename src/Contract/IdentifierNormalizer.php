<?php

declare(strict_types=1);

namespace IdentiSpec\Contract;

use IdentiSpec\Enum\ValidationMode;
use IdentiSpec\Normalization\NormalizationResult;
use SensitiveParameter;

interface IdentifierNormalizer
{
    public function normalize(
        #[SensitiveParameter]
        string $value,
        ValidationMode $mode,
    ): NormalizationResult;
}
