<?php

declare(strict_types=1);

namespace IdentiSpec\Tests\Contract;

use IdentiSpec\Contract\IdentifierTypeValidator;
use IdentiSpec\Enum\ValidationCapability;
use IdentiSpec\Enum\ValidationMode;
use IdentiSpec\Enum\ValidationStatus;
use IdentiSpec\ValidationOptions;
use PHPUnit\Framework\TestCase;

abstract class IdentifierTypeValidatorContractTestCase extends TestCase
{
    abstract protected function validator(): IdentifierTypeValidator;

    /** @return iterable<string> */
    abstract protected function validExamples(): iterable;

    /** @return iterable<string> */
    abstract protected function invalidExamples(): iterable;

    final public function testContractValidExamples(): void
    {
        $validator = $this->validator();
        $hasExample = false;

        foreach ($this->validExamples() as $value) {
            $hasExample = true;
            $result = $validator->validate($value, new ValidationOptions());

            self::assertSame(ValidationStatus::VALID, $result->status());
            self::assertTrue($result->key()->equals($validator->definition()->key()));
            self::assertNotNull($result->metadata());
            self::assertStringNotContainsString($value, json_encode($result->toSafeArray(), JSON_THROW_ON_ERROR));
        }

        self::assertTrue($hasExample, 'Contract requires at least one valid example.');
    }

    final public function testContractInvalidExamples(): void
    {
        $validator = $this->validator();
        $hasExample = false;

        foreach ($this->invalidExamples() as $value) {
            $hasExample = true;
            $result = $validator->validate($value, new ValidationOptions());

            self::assertSame(ValidationStatus::INVALID, $result->status());
            self::assertTrue($result->key()->equals($validator->definition()->key()));
            self::assertNotSame([], $result->issues());
            self::assertNotNull($result->metadata());
        }

        self::assertTrue($hasExample, 'Contract requires at least one invalid example.');
    }

    final public function testContractDefinitionDeclaresCoreCapabilities(): void
    {
        $definition = $this->validator()->definition();

        self::assertTrue($definition->hasCapability(ValidationCapability::CHARACTERS));
        self::assertTrue($definition->hasCapability(ValidationCapability::LENGTH));
    }

    final public function testContractHandlesArbitraryValuesDeterministically(): void
    {
        $validator = $this->validator();
        $options = new ValidationOptions(ValidationMode::STRICT);
        $values = [
            '',
            ' ',
            "\0",
            "A\0B",
            'Ł',
            'RAW_VALUE_MUST_NOT_LEAK',
            str_repeat('9', 4_096),
        ];

        foreach ($values as $value) {
            $first = $validator->validate($value, $options);
            $second = $validator->validate($value, $options);

            self::assertNotSame(ValidationStatus::UNSUPPORTED, $first->status());
            self::assertTrue($first->key()->equals($validator->definition()->key()));
            self::assertSame($first->toSafeArray(), $second->toSafeArray());

            if (strlen($value) >= 4) {
                self::assertStringNotContainsString(
                    $value,
                    json_encode($first->toSafeArray(), JSON_THROW_ON_ERROR),
                );
            }
        }
    }
}
