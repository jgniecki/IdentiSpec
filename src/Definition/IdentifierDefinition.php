<?php

declare(strict_types=1);

namespace IdentiSpec\Definition;

use IdentiSpec\Enum\IdentifierCategory;
use IdentiSpec\Enum\PrefixPolicy;
use IdentiSpec\Enum\ValidationCapability;
use IdentiSpec\Enum\ValidationLevel;
use IdentiSpec\Internal\ObjectList;
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

        $normalizedCapabilities = ObjectList::normalize(
            $capabilities,
            ValidationCapability::class,
            false,
        );
        $indexedCapabilities = [];

        foreach ($normalizedCapabilities as $capability) {
            if (isset($indexedCapabilities[$capability->value])) {
                throw new InvalidArgumentException(sprintf(
                    'Validation capability %s is declared more than once.',
                    $capability->value,
                ));
            }

            $indexedCapabilities[$capability->value] = $capability;
        }

        foreach ([ValidationCapability::CHARACTERS, ValidationCapability::LENGTH] as $requiredCapability) {
            if (!isset($indexedCapabilities[$requiredCapability->value])) {
                throw new InvalidArgumentException(sprintf(
                    'Identifier must declare the %s capability.',
                    $requiredCapability->value,
                ));
            }
        }

        $hasChecksum = isset($indexedCapabilities[ValidationCapability::CHECKSUM->value]);

        if ($this->validationLevel === ValidationLevel::FORMAT_ONLY && $hasChecksum) {
            throw new InvalidArgumentException('FORMAT_ONLY cannot declare the CHECKSUM capability.');
        }

        if ($this->validationLevel === ValidationLevel::FORMAT_AND_CHECKSUM && !$hasChecksum) {
            throw new InvalidArgumentException('FORMAT_AND_CHECKSUM requires the CHECKSUM capability.');
        }

        $hasPrefixPolicy = $this->canonicalFormat->prefix()->policy() !== PrefixPolicy::NONE;
        $hasPrefixCapability = isset($indexedCapabilities[ValidationCapability::PREFIX->value]);

        if ($hasPrefixPolicy && !$hasPrefixCapability) {
            throw new InvalidArgumentException('A non-NONE prefix policy requires the PREFIX capability.');
        }

        if (!$hasPrefixPolicy && $hasPrefixCapability) {
            throw new InvalidArgumentException('The PREFIX capability requires a non-NONE prefix policy.');
        }

        $this->displayName = $displayName;
        /** @var non-empty-list<ValidationCapability> $normalizedCapabilities */
        $capabilityList = array_values($indexedCapabilities);
        $this->capabilities = $capabilityList;
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

    public function hasCapability(ValidationCapability $capability): bool
    {
        return in_array($capability, $this->capabilities, true);
    }

    public function metadata(): RuleSetMetadata
    {
        return $this->metadata;
    }
}
