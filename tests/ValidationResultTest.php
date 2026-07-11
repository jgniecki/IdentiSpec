<?php

declare(strict_types=1);

namespace IdentiSpec\Tests;

use IdentiSpec\Diagnostic\DiagnosticCode;
use IdentiSpec\Diagnostic\NormalizationTransformation;
use IdentiSpec\Diagnostic\ValidationIssue;
use IdentiSpec\Diagnostic\ValidationWarning;
use IdentiSpec\Enum\ValidationStatus;
use IdentiSpec\Tests\Fixture\TestIdentifierTypeValidator;
use IdentiSpec\ValidationResult;
use IdentiSpec\Value\IdentifierKey;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class ValidationResultTest extends TestCase
{
    public function testValidFactoryBuildsConsistentResult(): void
    {
        $definition = TestIdentifierTypeValidator::definitionFor();
        $result = ValidationResult::valid($definition, 'OK');

        self::assertTrue($result->isValid());
        self::assertSame(ValidationStatus::VALID, $result->status());
        self::assertSame($definition->validationLevel(), $result->level());
        self::assertSame($definition->metadata(), $result->metadata());
        self::assertSame([], $result->issues());
    }

    public function testInvalidFactoryRequiresIssue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ValidationResult::invalid(TestIdentifierTypeValidator::definitionFor(), null, []);
    }

    public function testInvalidFactoryRejectsEmptyNormalizedValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ValidationResult::invalid(
            TestIdentifierTypeValidator::definitionFor(),
            '',
            [new ValidationIssue(DiagnosticCode::invalidFormat())],
        );
    }

    public function testRuntimeRejectsInvalidDiagnosticCollectionItem(): void
    {
        $method = new ReflectionMethod(ValidationResult::class, 'valid');

        $this->expectException(InvalidArgumentException::class);

        $method->invoke(
            null,
            TestIdentifierTypeValidator::definitionFor(),
            'OK',
            [new \stdClass()],
        );
    }

    public function testUnsupportedHasNoRuleSetOrNormalizedValue(): void
    {
        $result = ValidationResult::unsupported(IdentifierKey::fromParts('YY', 'UNKNOWN'));

        self::assertSame(ValidationStatus::UNSUPPORTED, $result->status());
        self::assertNull($result->level());
        self::assertNull($result->metadata());
        self::assertNull($result->normalizedValue());
        self::assertTrue($result->issues()[0]->code()->equals(DiagnosticCode::unsupportedIdentifier()));
    }

    public function testSafeArrayRedactsValueAndOmitsAllContexts(): void
    {
        $secret = 'VALUE_THAT_MUST_NOT_LEAK';
        $definition = TestIdentifierTypeValidator::definitionFor();
        $result = ValidationResult::invalid(
            $definition,
            'OK',
            [new ValidationIssue(DiagnosticCode::invalidFormat(), 1, ['secret' => $secret])],
            [new ValidationWarning(DiagnosticCode::fromString('TEST_WARNING'), 0, ['secret' => $secret])],
            [new NormalizationTransformation(
                DiagnosticCode::removedSeparator(),
                1,
                ['secret' => $secret],
            )],
        );

        $safe = $result->toSafeArray();
        $encoded = json_encode($safe, JSON_THROW_ON_ERROR);

        self::assertSame('[REDACTED]', $safe['masked_value']);
        self::assertStringNotContainsString($secret, $encoded);
        self::assertStringNotContainsString('context', $encoded);
        self::assertSame(
            ['code' => 'INVALID_FORMAT', 'position' => 1],
            $safe['issues'][0],
        );
        self::assertSame([
            'status' => 'INVALID',
            'level' => 'FORMAT_ONLY',
            'jurisdiction_code' => 'XX',
            'identifier_type' => 'TEST',
            'masked_value' => '[REDACTED]',
            'issues' => [['code' => 'INVALID_FORMAT', 'position' => 1]],
            'warnings' => [['code' => 'TEST_WARNING', 'position' => 0]],
            'transformations' => [['code' => 'REMOVED_SEPARATOR', 'position' => 1]],
            'rule_set' => ['id' => 'XX_TEST', 'version' => '1.0.0'],
        ], $safe);
    }

    public function testUnsupportedSafeArrayHasExactStableShape(): void
    {
        self::assertSame([
            'status' => 'UNSUPPORTED',
            'level' => null,
            'jurisdiction_code' => 'YY',
            'identifier_type' => 'UNKNOWN',
            'masked_value' => null,
            'issues' => [['code' => 'UNSUPPORTED_IDENTIFIER', 'position' => null]],
            'warnings' => [],
            'transformations' => [],
            'rule_set' => null,
        ], ValidationResult::unsupported(IdentifierKey::fromParts('YY', 'UNKNOWN'))->toSafeArray());
    }
}
