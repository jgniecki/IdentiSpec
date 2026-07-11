<?php

declare(strict_types=1);

namespace IdentiSpec\Definition;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class RuleSetMetadata
{
    /** @var non-empty-list<RuleSource> */
    private array $sources;
    private string $ruleSetId;
    private string $version;

    /**
     * @param list<RuleSource> $sources
     */
    public function __construct(
        string $ruleSetId,
        string $version,
        array $sources,
        private DateTimeImmutable $reviewedAt,
        private ?DateTimeImmutable $effectiveFrom = null,
        private ?DateTimeImmutable $effectiveTo = null,
    ) {
        $ruleSetId = trim($ruleSetId);
        $version = trim($version);

        if (preg_match('/\A[A-Z][A-Z0-9]*(?:_[A-Z0-9]+)*\z/D', $ruleSetId) !== 1) {
            throw new InvalidArgumentException('Rule-set identifier must use UPPER_SNAKE_CASE.');
        }

        if ($version === '') {
            throw new InvalidArgumentException('Rule-set version cannot be empty.');
        }

        if ($sources === []) {
            throw new InvalidArgumentException('Rule-set metadata must contain at least one source.');
        }

        if ($effectiveFrom !== null && $effectiveTo !== null && $effectiveFrom > $effectiveTo) {
            throw new InvalidArgumentException('Rule-set effectiveFrom cannot be later than effectiveTo.');
        }

        $this->ruleSetId = $ruleSetId;
        $this->version = $version;
        /** @var non-empty-list<RuleSource> $normalizedSources */
        $normalizedSources = $sources;
        $this->sources = $normalizedSources;
    }

    public function ruleSetId(): string
    {
        return $this->ruleSetId;
    }

    public function version(): string
    {
        return $this->version;
    }

    /** @return non-empty-list<RuleSource> */
    public function sources(): array
    {
        return $this->sources;
    }

    public function reviewedAt(): DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function effectiveFrom(): ?DateTimeImmutable
    {
        return $this->effectiveFrom;
    }

    public function effectiveTo(): ?DateTimeImmutable
    {
        return $this->effectiveTo;
    }
}
