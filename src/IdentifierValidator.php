<?php

declare(strict_types=1);

namespace IdentiSpec;

use IdentiSpec\Registry\ValidatorRegistry;
use SensitiveParameter;

final readonly class IdentifierValidator
{
    public function __construct(private ValidatorRegistry $registry) {}

    public function validate(
        #[SensitiveParameter]
        IdentifierInput $input,
        ?ValidationOptions $options = null,
    ): ValidationResult {
        $validator = $this->registry->find($input->key());

        if ($validator === null) {
            return ValidationResult::unsupported($input->key());
        }

        $result = $validator->validate(
            $input->value(),
            $options ?? new ValidationOptions(),
        );
        $result->assertConsistentWith($validator->definition());

        return $result;
    }
}
