<?php

declare(strict_types=1);

namespace IdentiSpec\Definition;

use IdentiSpec\Enum\PrefixPolicy;
use InvalidArgumentException;

final readonly class PrefixDefinition
{
    private ?string $literal;

    public function __construct(
        private PrefixPolicy $policy = PrefixPolicy::NONE,
        ?string $literal = null,
        private bool $includedInCanonicalValue = false,
    ) {
        $literal = $literal === null ? null : trim($literal);

        if ($this->policy === PrefixPolicy::NONE) {
            if ($literal !== null || $this->includedInCanonicalValue) {
                throw new InvalidArgumentException('NONE prefix policy cannot declare a literal or canonical inclusion.');
            }

            $this->literal = null;

            return;
        }

        if ($literal === null || $literal === '') {
            throw new InvalidArgumentException(sprintf(
                '%s prefix policy requires a literal prefix.',
                $this->policy->value,
            ));
        }

        if (preg_match('/\A[A-Z0-9]+\z/', $literal) !== 1) {
            throw new InvalidArgumentException('Literal prefix must use uppercase ASCII letters or digits.');
        }

        if ($this->policy === PrefixPolicy::REQUIRED && !$this->includedInCanonicalValue) {
            throw new InvalidArgumentException('A required prefix must be included in the canonical value.');
        }

        if ($this->policy !== PrefixPolicy::REQUIRED && $this->includedInCanonicalValue) {
            throw new InvalidArgumentException(sprintf(
                '%s prefix policy cannot include the prefix in the canonical value.',
                $this->policy->value,
            ));
        }

        $this->literal = $literal;
    }

    public static function none(): self
    {
        return new self();
    }

    public static function required(string $literal): self
    {
        return new self(PrefixPolicy::REQUIRED, $literal, true);
    }

    public static function optional(string $literal): self
    {
        return new self(PrefixPolicy::OPTIONAL, $literal);
    }

    public static function forbidden(string $literal): self
    {
        return new self(PrefixPolicy::FORBIDDEN, $literal);
    }

    public function policy(): PrefixPolicy
    {
        return $this->policy;
    }

    public function literal(): ?string
    {
        return $this->literal;
    }

    public function includedInCanonicalValue(): bool
    {
        return $this->includedInCanonicalValue;
    }
}
