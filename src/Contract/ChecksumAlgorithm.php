<?php

declare(strict_types=1);

namespace IdentiSpec\Contract;

use SensitiveParameter;

interface ChecksumAlgorithm
{
    public function calculate(
        #[SensitiveParameter]
        string $payload,
    ): int;
}
