<?php

declare(strict_types=1);

namespace IdentiSpec\Tests\Benchmark;

use IdentiSpec\Enum\LetterCasePolicy;
use IdentiSpec\Enum\ValidationMode;
use IdentiSpec\IdentifierInput;
use IdentiSpec\IdentifierValidator;
use IdentiSpec\Normalization\AsciiAlphanumericNormalizer;
use IdentiSpec\Normalization\NumericNormalizer;
use IdentiSpec\Registry\ValidatorRegistry;
use IdentiSpec\Tests\Fixture\TestIdentifierTypeValidator;
use IdentiSpec\ValidationResult;
use PhpBench\Attributes as Bench;

#[Bench\Iterations(5)]
#[Bench\Revs(10_000)]
final class CoreBench
{
    private TestIdentifierTypeValidator $strategy;
    private ValidatorRegistry $registry;
    private IdentifierValidator $validator;
    private NumericNormalizer $numericNormalizer;
    private AsciiAlphanumericNormalizer $alphanumericNormalizer;
    private ValidationResult $result;

    public function __construct()
    {
        $this->strategy = new TestIdentifierTypeValidator();
        $this->registry = new ValidatorRegistry([$this->strategy]);
        $this->validator = new IdentifierValidator($this->registry);
        $this->numericNormalizer = new NumericNormalizer(['-', ' ']);
        $this->alphanumericNormalizer = new AsciiAlphanumericNormalizer(
            ['-', ' '],
            LetterCasePolicy::UPPERCASE,
        );
        $this->result = ValidationResult::valid($this->strategy->definition(), 'OK');
    }

    public function benchRegistryConstructionAndLookup(): void
    {
        $registry = new ValidatorRegistry([$this->strategy]);
        if ($registry->find($this->strategy->definition()->key()) === null) {
            throw new \LogicException('Benchmark fixture must be registered.');
        }
    }

    public function benchFacadeRouting(): void
    {
        $this->validator->validate(new IdentifierInput('XX', 'TEST', 'OK'));
    }

    public function benchNumericNormalization(): void
    {
        $this->numericNormalizer->normalize('123-456 789', ValidationMode::LENIENT);
    }

    public function benchAsciiAlphanumericNormalization(): void
    {
        $this->alphanumericNormalizer->normalize('ab-12 cd', ValidationMode::LENIENT);
    }

    public function benchSafeSerialization(): void
    {
        $this->result->toSafeArray();
    }
}
