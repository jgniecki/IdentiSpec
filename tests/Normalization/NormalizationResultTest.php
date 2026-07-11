<?php

declare(strict_types=1);

namespace IdentiSpec\Tests\Normalization;

use IdentiSpec\Diagnostic\DiagnosticCode;
use IdentiSpec\Diagnostic\NormalizationTransformation;
use IdentiSpec\Diagnostic\ValidationIssue;
use IdentiSpec\Normalization\NormalizationResult;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class NormalizationResultTest extends TestCase
{
    public function testFailureRequiresIssue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        NormalizationResult::failure([]);
    }

    public function testRuntimeRejectsInvalidTransformationObject(): void
    {
        $method = new ReflectionMethod(NormalizationResult::class, 'success');

        $this->expectException(InvalidArgumentException::class);

        $method->invoke(null, 'OK', [new \stdClass()]);
    }

    public function testRuntimeRejectsInvalidIssueObject(): void
    {
        $method = new ReflectionMethod(NormalizationResult::class, 'failure');

        $this->expectException(InvalidArgumentException::class);

        $method->invoke(null, [new \stdClass()]);
    }

    public function testRuntimeRejectsNonListCollection(): void
    {
        $method = new ReflectionMethod(NormalizationResult::class, 'success');

        $this->expectException(InvalidArgumentException::class);

        $method->invoke(null, 'OK', [
            1 => new NormalizationTransformation(DiagnosticCode::removedSeparator(), 0),
        ]);
    }

    public function testSuccessAndFailureRemainDisjoint(): void
    {
        $success = NormalizationResult::success('OK');
        $failure = NormalizationResult::failure([
            new ValidationIssue(DiagnosticCode::invalidFormat()),
        ]);

        self::assertTrue($success->isSuccessful());
        self::assertSame([], $success->issues());
        self::assertFalse($failure->isSuccessful());
        self::assertNull($failure->normalizedValue());
    }
}
