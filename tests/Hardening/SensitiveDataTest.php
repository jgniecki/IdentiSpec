<?php

declare(strict_types=1);

namespace IdentiSpec\Tests\Hardening;

use IdentiSpec\Contract\IdentifierNormalizer;
use IdentiSpec\Contract\IdentifierTypeValidator;
use IdentiSpec\Diagnostic\DiagnosticCode;
use IdentiSpec\Diagnostic\NormalizationTransformation;
use IdentiSpec\Diagnostic\ValidationIssue;
use IdentiSpec\Diagnostic\ValidationWarning;
use IdentiSpec\IdentifierInput;
use IdentiSpec\IdentifierValidator;
use IdentiSpec\Normalization\NormalizationResult;
use IdentiSpec\Normalization\NumericNormalizer;
use IdentiSpec\Tests\Fixture\TestIdentifierTypeValidator;
use IdentiSpec\ValidationResult;
use InvalidArgumentException;
use ReflectionMethod;
use SensitiveParameter;

final class SensitiveDataTest extends \PHPUnit\Framework\TestCase
{
    private const CANARY = 'PRIVATE_IDENTIFIER_CANARY_847261';

    public function testSensitiveParametersAreMarkedAtPublicValueBoundaries(): void
    {
        self::assertParameterIsSensitive(new ReflectionMethod(IdentifierInput::class, '__construct'), 2);
        self::assertParameterIsSensitive(new ReflectionMethod(IdentifierValidator::class, 'validate'), 0);
        self::assertParameterIsSensitive(new ReflectionMethod(IdentifierTypeValidator::class, 'validate'), 0);
        self::assertParameterIsSensitive(new ReflectionMethod(IdentifierNormalizer::class, 'normalize'), 0);
        self::assertParameterIsSensitive(new ReflectionMethod(NumericNormalizer::class, 'normalize'), 0);
        self::assertParameterIsSensitive(new ReflectionMethod(NormalizationResult::class, 'success'), 0);
        self::assertParameterIsSensitive(new ReflectionMethod(ValidationResult::class, 'valid'), 1);
        self::assertParameterIsSensitive(new ReflectionMethod(ValidationResult::class, 'invalid'), 1);
    }

    public function testSafeResultRecursivelyOmitsValuesAndDiagnosticContext(): void
    {
        $definition = TestIdentifierTypeValidator::definitionFor();
        $result = ValidationResult::invalid(
            $definition,
            self::CANARY,
            [new ValidationIssue(DiagnosticCode::invalidFormat(), 0, ['value' => self::CANARY])],
            [new ValidationWarning(DiagnosticCode::fromString('TEST_WARNING'), 1, ['value' => self::CANARY])],
            [
                new NormalizationTransformation(
                    DiagnosticCode::removedSeparator(),
                    2,
                    ['value' => self::CANARY],
                ),
            ],
        );

        $encoded = json_encode($result->toSafeArray(), JSON_THROW_ON_ERROR);

        self::assertStringNotContainsString(self::CANARY, $encoded);
        self::assertStringNotContainsString('context', $encoded);
        self::assertStringContainsString('[REDACTED]', $encoded);
    }

    public function testProgrammerExceptionsDoNotIncludeIdentifierValue(): void
    {
        try {
            new IdentifierInput('', 'TEST', self::CANARY);
            self::fail('Invalid selector must throw.');
        } catch (\Throwable $exception) {
            self::assertStringNotContainsString(self::CANARY, $exception->getMessage());
        }

        try {
            (new \ReflectionClass(ValidationIssue::class))->newInstanceArgs([
                DiagnosticCode::invalidFormat(),
                null,
                ['value' => [self::CANARY]],
            ]);
            self::fail('Invalid context must throw.');
        } catch (\Throwable $exception) {
            self::assertStringNotContainsString(self::CANARY, $exception->getMessage());
        }
    }

    public function testDiagnosticRuntimeInvariantsRejectInvalidValues(): void
    {
        foreach ([ValidationIssue::class, ValidationWarning::class] as $diagnosticClass) {
            try {
                (new \ReflectionClass($diagnosticClass))->newInstanceArgs([
                    DiagnosticCode::invalidFormat(),
                    -1,
                ]);
                self::fail('Negative position must throw.');
            } catch (InvalidArgumentException $exception) {
                self::assertStringNotContainsString(self::CANARY, $exception->getMessage());
            }
        }

        foreach ([[0 => 'value'], ['value' => []]] as $context) {
            try {
                (new \ReflectionClass(ValidationIssue::class))->newInstanceArgs([
                    DiagnosticCode::invalidFormat(),
                    null,
                    $context,
                ]);
                self::fail('Invalid context must throw.');
            } catch (InvalidArgumentException $exception) {
                self::assertStringNotContainsString(self::CANARY, $exception->getMessage());
            }
        }
    }

    private static function assertParameterIsSensitive(ReflectionMethod $method, int $position): void
    {
        $parameter = $method->getParameters()[$position];
        $attributes = $parameter->getAttributes(SensitiveParameter::class);

        self::assertCount(
            1,
            $attributes,
            sprintf('%s::%s($%s) must be sensitive.', $method->class, $method->name, $parameter->name),
        );
    }
}
