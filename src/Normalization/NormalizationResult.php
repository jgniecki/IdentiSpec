<?php

declare(strict_types=1);

namespace IdentiSpec\Normalization;

use IdentiSpec\Diagnostic\NormalizationTransformation;
use IdentiSpec\Diagnostic\ValidationIssue;
use InvalidArgumentException;

final readonly class NormalizationResult
{
    /**
     * @param list<ValidationIssue> $issues
     * @param list<NormalizationTransformation> $transformations
     */
    private function __construct(
        private ?string $normalizedValue,
        private array $issues,
        private array $transformations,
    ) {
    }

    /**
     * @param list<NormalizationTransformation> $transformations
     */
    public static function success(string $normalizedValue, array $transformations = []): self
    {
        if ($normalizedValue === '') {
            throw new InvalidArgumentException('Successful normalization requires a non-empty value.');
        }

        return new self($normalizedValue, [], $transformations);
    }

    /**
     * @param list<ValidationIssue> $issues
     * @param list<NormalizationTransformation> $transformations
     */
    public static function failure(array $issues, array $transformations = []): self
    {
        if ($issues === []) {
            throw new InvalidArgumentException('Failed normalization requires at least one issue.');
        }

        return new self(null, $issues, $transformations);
    }

    public function isSuccessful(): bool
    {
        return $this->issues === [];
    }

    public function normalizedValue(): ?string
    {
        return $this->normalizedValue;
    }

    /** @return list<ValidationIssue> */
    public function issues(): array
    {
        return $this->issues;
    }

    /** @return list<NormalizationTransformation> */
    public function transformations(): array
    {
        return $this->transformations;
    }
}
