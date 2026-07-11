<?php

declare(strict_types=1);

namespace IdentiSpec\Tests\Contract;

use IdentiSpec\Contract\IdentifierTypeValidator;
use IdentiSpec\Tests\Fixture\TestIdentifierTypeValidator;

final class TestIdentifierTypeValidatorContractTest extends IdentifierTypeValidatorContractTestCase
{
    protected function validator(): IdentifierTypeValidator
    {
        return new TestIdentifierTypeValidator();
    }

    protected function validExamples(): iterable
    {
        yield 'OK';
    }

    protected function invalidExamples(): iterable
    {
        yield 'NO';
    }
}
