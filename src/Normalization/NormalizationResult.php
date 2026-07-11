<?php

declare(strict_types=1);

namespace IdentiSpec\Normalization;

use IdentiSpec\Diagnostic\NormalizationTransformation;
use IdentiSpec\Diagnostic\ValidationIssue;
use IdentiSpec\Internal\ObjectList;
use InvalidArgumentException;
use SensitiveParameter;

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
    ) {}

    /**
     * @param list<NormalizationTransformation> $transformations
     */
    public static function success(
        #[SensitiveParameter]
        string $normalizedValue,
        array $transformations = [],
    ): self {
        if ($normalizedValue === '') {
            throw new InvalidArgumentException('Successful normalization requires a non-empty value.');
        }

        $normalizedTransformations = ObjectList::normalize(
            $transformations,
            NormalizationTransformation::class,
        );

        return new self($normalizedValue, [], $normalizedTransformations);
    }

    /**
     * @param list<ValidationIssue> $issues
     * @param list<NormalizationTransformation> $transformations
     */
    public static function failure(array $issues, array $transformations = []): self
    {
        $normalizedIssues = ObjectList::normalize($issues, ValidationIssue::class, true);
        $normalizedTransformations = ObjectList::normalize(
            $transformations,
            NormalizationTransformation::class,
        );

        return new self(null, $normalizedIssues, $normalizedTransformations);
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
