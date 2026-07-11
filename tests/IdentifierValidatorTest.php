<?php

declare(strict_types=1);

namespace IdentiSpec\Tests;

use IdentiSpec\Contract\IdentifierTypeValidator;
use IdentiSpec\Definition\IdentifierDefinition;
use IdentiSpec\Enum\ValidationMode;
use IdentiSpec\Enum\ValidationStatus;
use IdentiSpec\Exception\InconsistentValidationResult;
use IdentiSpec\IdentifierInput;
use IdentiSpec\IdentifierValidator;
use IdentiSpec\Registry\ValidatorRegistry;
use IdentiSpec\Tests\Fixture\TestIdentifierTypeValidator;
use IdentiSpec\ValidationOptions;
use IdentiSpec\ValidationResult;
use PHPUnit\Framework\TestCase;

final class IdentifierValidatorTest extends TestCase
{
    public function testRoutesValueAndUsesStrictByDefault(): void
    {
        $strategy = new TestIdentifierTypeValidator();
        $validator = new IdentifierValidator(new ValidatorRegistry([$strategy]));

        $result = $validator->validate(new IdentifierInput('XX', 'TEST', 'OK'));

        self::assertTrue($result->isValid());
        self::assertSame('OK', $strategy->lastValue());
        self::assertSame(ValidationMode::STRICT, $strategy->lastOptions()?->mode());
    }

    public function testForwardsExplicitOptions(): void
    {
        $strategy = new TestIdentifierTypeValidator();
        $validator = new IdentifierValidator(new ValidatorRegistry([$strategy]));

        $options = new ValidationOptions(ValidationMode::LENIENT);
        $validator->validate(new IdentifierInput('XX', 'TEST', 'OK'), $options);

        self::assertSame($options, $strategy->lastOptions());
    }

    public function testUnknownKeyIsUnsupported(): void
    {
        $validator = new IdentifierValidator(new ValidatorRegistry());

        $result = $validator->validate(new IdentifierInput('YY', 'UNKNOWN', 'anything'));

        self::assertSame(ValidationStatus::UNSUPPORTED, $result->status());
    }

    public function testRejectsMismatchedResultKey(): void
    {
        $definition = TestIdentifierTypeValidator::definitionFor('XX', 'TEST');
        $otherDefinition = TestIdentifierTypeValidator::definitionFor('YY', 'OTHER');
        $strategy = new class ($definition, $otherDefinition) implements IdentifierTypeValidator {
            public function __construct(
                private readonly IdentifierDefinition $definition,
                private readonly IdentifierDefinition $otherDefinition,
            ) {
            }

            public function definition(): IdentifierDefinition
            {
                return $this->definition;
            }

            public function validate(string $value, ValidationOptions $options): ValidationResult
            {
                return ValidationResult::valid($this->otherDefinition, 'OK');
            }
        };

        $validator = new IdentifierValidator(new ValidatorRegistry([$strategy]));

        $this->expectException(InconsistentValidationResult::class);

        $validator->validate(new IdentifierInput('XX', 'TEST', 'OK'));
    }

    public function testRejectsUnsupportedFromRegisteredStrategy(): void
    {
        $definition = TestIdentifierTypeValidator::definitionFor();
        $strategy = new class ($definition) implements IdentifierTypeValidator {
            public function __construct(private readonly IdentifierDefinition $definition)
            {
            }

            public function definition(): IdentifierDefinition
            {
                return $this->definition;
            }

            public function validate(string $value, ValidationOptions $options): ValidationResult
            {
                return ValidationResult::unsupported($this->definition->key());
            }
        };

        $validator = new IdentifierValidator(new ValidatorRegistry([$strategy]));

        $this->expectException(InconsistentValidationResult::class);

        $validator->validate(new IdentifierInput('XX', 'TEST', 'OK'));
    }
}
