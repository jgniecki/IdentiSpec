<?php

declare(strict_types=1);

namespace IdentiSpec\Definition;

use IdentiSpec\Enum\IdentifierCategory;
use IdentiSpec\Enum\ValidationCapability;
use IdentiSpec\Enum\ValidationLevel;
use IdentiSpec\Value\IdentifierKey;
use InvalidArgumentException;

final readonly class IdentifierDefinition
{
    /** @var non-empty-list<ValidationCapability> */
    private array $capabilities;
    private string $displayName;

    /**
     * @param list<ValidationCapability> $capabilities
     */
    public function __construct(
        private IdentifierKey $key,
        string $displayName,
        private IdentifierCategory $category,
        private CanonicalFormat $canonicalFormat,
        private ValidationLevel $validationLevel,
        array $capabilities,
        private RuleSetMetadata $metadata,
    ) {
        $displayName = trim($displayName);

        if ($displayName === '') {
            throw new InvalidArgumentException('Identifier display name cannot be empty.');
        }

        if ($capabilities === []) {
            throw new InvalidArgumentException('Identifier must declare at least one validation capability.');
        }

        $uniqueCapabilities = [];

        foreach ($capabilities as $capability) {
            $uniqueCapabilities[$capability->value] = $capability;
        }

        $this->displayName = $displayName;
        /** @var non-empty-list<ValidationCapability> $normalizedCapabilities */
        $normalizedCapabilities = array_values($uniqueCapabilities);
        $this->capabilities = $normalizedCapabilities;
    }

    public function key(): IdentifierKey
    {
        return $this->key;
    }

    public function displayName(): string
    {
        return $this->displayName;
    }

    public function category(): IdentifierCategory
    {
        return $this->category;
    }

    public function canonicalFormat(): CanonicalFormat
    {
        return $this->canonicalFormat;
    }

    public function validationLevel(): ValidationLevel
    {
        return $this->validationLevel;
    }

    /** @return non-empty-list<ValidationCapability> */
    public function capabilities(): array
    {
        return $this->capabilities;
    }

    public function metadata(): RuleSetMetadata
    {
        return $this->metadata;
    }
}
