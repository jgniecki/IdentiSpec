<?php

declare(strict_types=1);

namespace IdentiSpec\Diagnostic;

use InvalidArgumentException;
use SensitiveParameter;

final readonly class NormalizationTransformation
{
    /** @var array<string, scalar|null> */
    private array $context;

    /**
     * @param array<string, scalar|null> $context
     */
    public function __construct(
        private DiagnosticCode $code,
        private int $position,
        #[SensitiveParameter]
        array $context = [],
    ) {
        if ($position < 0) {
            throw new InvalidArgumentException('Transformation position cannot be negative.');
        }

        $this->context = DiagnosticContext::normalize($context);
    }

    public function code(): DiagnosticCode
    {
        return $this->code;
    }

    public function position(): int
    {
        return $this->position;
    }

    /** @return array<string, scalar|null> */
    public function context(): array
    {
        return $this->context;
    }
}
