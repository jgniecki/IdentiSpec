<?php

declare(strict_types=1);

namespace IdentiSpec;

use IdentiSpec\Enum\ValidationMode;

final readonly class ValidationOptions
{
    public function __construct(
        private ValidationMode $mode = ValidationMode::STRICT,
    ) {
    }

    public function mode(): ValidationMode
    {
        return $this->mode;
    }
}
