<?php

declare(strict_types=1);

namespace IdentiSpec\Contract;

use IdentiSpec\Definition\IdentifierDefinition;
use IdentiSpec\ValidationOptions;
use IdentiSpec\ValidationResult;

interface IdentifierTypeValidator
{
    public function definition(): IdentifierDefinition;

    public function validate(string $value, ValidationOptions $options): ValidationResult;
}
