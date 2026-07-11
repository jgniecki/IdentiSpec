<?php

declare(strict_types=1);

namespace IdentiSpec;

use IdentiSpec\Definition\IdentifierDefinition;
use IdentiSpec\Definition\RuleSetMetadata;
use IdentiSpec\Diagnostic\DiagnosticCode;
use IdentiSpec\Diagnostic\NormalizationTransformation;
use IdentiSpec\Diagnostic\ValidationIssue;
use IdentiSpec\Diagnostic\ValidationWarning;
use IdentiSpec\Enum\ValidationLevel;
use IdentiSpec\Enum\ValidationStatus;
use IdentiSpec\Value\IdentifierKey;
use InvalidArgumentException;

final readonly class ValidationResult
{
    /**
     * @param list<ValidationIssue> $issues
     * @param list<ValidationWarning> $warnings
     * @param list<NormalizationTransformation> $transformations
     */
    private function __construct(
        private ValidationStatus $status,
        private IdentifierKey $key,
        private ?ValidationLevel $level,
        private ?string $normalizedValue,
        private array $issues,
        private array $warnings,
        private array $transformations,
        private ?RuleSetMetadata $metadata,
    ) {
    }

    /**
     * @param list<ValidationWarning> $warnings
     * @param list<NormalizationTransformation> $transformations
     */
    public static function valid(
        IdentifierDefinition $definition,
        string $normalizedValue,
        array $warnings = [],
        array $transformations = [],
    ): self {
        if ($normalizedValue === '') {
            throw new InvalidArgumentException('A valid result requires a non-empty normalized value.');
        }

        return new self(
            ValidationStatus::VALID,
            $definition->key(),
            $definition->validationLevel(),
            $normalizedValue,
            [],
            $warnings,
            $transformations,
            $definition->metadata(),
        );
    }

    /**
     * @param list<ValidationIssue> $issues
     * @param list<ValidationWarning> $warnings
     * @param list<NormalizationTransformation> $transformations
     */
    public static function invalid(
        IdentifierDefinition $definition,
        ?string $normalizedValue,
        array $issues,
        array $warnings = [],
        array $transformations = [],
    ): self {
        if ($issues === []) {
            throw new InvalidArgumentException('An invalid result requires at least one issue.');
        }

        return new self(
            ValidationStatus::INVALID,
            $definition->key(),
            $definition->validationLevel(),
            $normalizedValue,
            $issues,
            $warnings,
            $transformations,
            $definition->metadata(),
        );
    }

    public static function unsupported(IdentifierKey $key): self
    {
        return new self(
            ValidationStatus::UNSUPPORTED,
            $key,
            null,
            null,
            [new ValidationIssue(DiagnosticCode::unsupportedIdentifier())],
            [],
            [],
            null,
        );
    }

    public function status(): ValidationStatus
    {
        return $this->status;
    }

    public function key(): IdentifierKey
    {
        return $this->key;
    }

    public function level(): ?ValidationLevel
    {
        return $this->level;
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

    /** @return list<ValidationWarning> */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /** @return list<NormalizationTransformation> */
    public function transformations(): array
    {
        return $this->transformations;
    }

    public function metadata(): ?RuleSetMetadata
    {
        return $this->metadata;
    }

    public function isValid(): bool
    {
        return $this->status === ValidationStatus::VALID;
    }

    /**
     * @return array{
     *     status: string,
     *     level: string|null,
     *     jurisdiction_code: string,
     *     identifier_type: string,
     *     masked_value: string|null,
     *     issues: list<array{code: string, position: int|null}>,
     *     warnings: list<array{code: string, position: int|null}>,
     *     transformations: list<array{code: string, position: int}>,
     *     rule_set: array{id: string, version: string}|null
     * }
     */
    public function toSafeArray(): array
    {
        return [
            'status' => $this->status->value,
            'level' => $this->level?->value,
            'jurisdiction_code' => $this->key->jurisdictionCode(),
            'identifier_type' => $this->key->identifierType(),
            'masked_value' => $this->normalizedValue === null ? null : '[REDACTED]',
            'issues' => array_map(
                static fn (ValidationIssue $issue): array => [
                    'code' => $issue->code()->value(),
                    'position' => $issue->position(),
                ],
                $this->issues,
            ),
            'warnings' => array_map(
                static fn (ValidationWarning $warning): array => [
                    'code' => $warning->code()->value(),
                    'position' => $warning->position(),
                ],
                $this->warnings,
            ),
            'transformations' => array_map(
                static fn (NormalizationTransformation $transformation): array => [
                    'code' => $transformation->code()->value(),
                    'position' => $transformation->position(),
                ],
                $this->transformations,
            ),
            'rule_set' => $this->metadata === null ? null : [
                'id' => $this->metadata->ruleSetId(),
                'version' => $this->metadata->version(),
            ],
        ];
    }
}
