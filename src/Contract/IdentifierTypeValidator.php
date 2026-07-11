<?php

declare(strict_types=1);

namespace IdentiSpec\Contract;

use IdentiSpec\Definition\IdentifierDefinition;
use IdentiSpec\ValidationOptions;
use IdentiSpec\ValidationResult;
use SensitiveParameter;

interface IdentifierTypeValidator
{
    public function definition(): IdentifierDefinition;

    public function validate(
        #[SensitiveParameter]
        string $value,
        ValidationOptions $options,
    ): ValidationResult;
}
