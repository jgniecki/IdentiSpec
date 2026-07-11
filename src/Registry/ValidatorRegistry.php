<?php

declare(strict_types=1);

namespace IdentiSpec\Registry;

use IdentiSpec\Contract\IdentifierTypeValidator;
use IdentiSpec\Definition\IdentifierDefinition;
use IdentiSpec\Exception\DuplicateIdentifierValidator;
use IdentiSpec\Value\IdentifierKey;

final readonly class ValidatorRegistry
{
    /** @var array<string, IdentifierTypeValidator> */
    private array $validators;

    /**
     * @param iterable<IdentifierTypeValidator> $validators
     */
    public function __construct(iterable $validators = [])
    {
        $indexedValidators = [];

        foreach ($validators as $validator) {
            $key = $validator->definition()->key()->toString();

            if (isset($indexedValidators[$key])) {
                throw DuplicateIdentifierValidator::forKey($key);
            }

            $indexedValidators[$key] = $validator;
        }

        ksort($indexedValidators);
        $this->validators = $indexedValidators;
    }

    public function find(IdentifierKey $key): ?IdentifierTypeValidator
    {
        return $this->validators[$key->toString()] ?? null;
    }

    /** @return list<IdentifierTypeValidator> */
    public function all(): array
    {
        return array_values($this->validators);
    }

    /** @return list<IdentifierDefinition> */
    public function definitions(): array
    {
        return array_map(
            static fn(IdentifierTypeValidator $validator): IdentifierDefinition => $validator->definition(),
            $this->all(),
        );
    }
}
