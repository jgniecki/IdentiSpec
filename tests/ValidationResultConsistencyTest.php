<?php

declare(strict_types=1);

namespace IdentiSpec\Tests;

use DateTimeImmutable;
use IdentiSpec\Definition\CanonicalFormat;
use IdentiSpec\Definition\IdentifierDefinition;
use IdentiSpec\Definition\RuleSetMetadata;
use IdentiSpec\Definition\RuleSource;
use IdentiSpec\Enum\CharacterSet;
use IdentiSpec\Enum\IdentifierCategory;
use IdentiSpec\Enum\ValidationCapability;
use IdentiSpec\Enum\ValidationLevel;
use IdentiSpec\Exception\InconsistentValidationResult;
use IdentiSpec\Tests\Fixture\TestIdentifierTypeValidator;
use IdentiSpec\ValidationResult;
use PHPUnit\Framework\TestCase;

final class ValidationResultConsistencyTest extends TestCase
{
    public function testAcceptsResultFromTheExactDefinitionContract(): void
    {
        $definition = TestIdentifierTypeValidator::definitionFor();
        $result = ValidationResult::valid($definition, 'OK');

        $result->assertConsistentWith($definition);

        self::assertSame($definition->validationLevel(), $result->level());
        self::assertSame($definition->metadata()->ruleSetId(), $result->metadata()->ruleSetId());
        self::assertSame($definition->metadata()->version(), $result->metadata()->version());
    }

    public function testRejectsMismatchedValidationLevelForTheSameKey(): void
    {
        $expected = TestIdentifierTypeValidator::definitionFor();
        $other = $this->definition(ValidationLevel::FULL_OFFLINE_RULES, 'XX_TEST', '1.0.0');
        $result = ValidationResult::valid($other, 'OK');

        $this->expectException(InconsistentValidationResult::class);
        $result->assertConsistentWith($expected);
    }

    public function testRejectsMismatchedRuleSetIdentifierForTheSameKeyAndLevel(): void
    {
        $expected = TestIdentifierTypeValidator::definitionFor();
        $other = $this->definition(ValidationLevel::FORMAT_ONLY, 'XX_OTHER', '1.0.0');
        $result = ValidationResult::valid($other, 'OK');

        $this->expectException(InconsistentValidationResult::class);
        $result->assertConsistentWith($expected);
    }

    public function testRejectsMismatchedRuleSetVersionForTheSameKeyAndLevel(): void
    {
        $expected = TestIdentifierTypeValidator::definitionFor();
        $other = $this->definition(ValidationLevel::FORMAT_ONLY, 'XX_TEST', '2.0.0');
        $result = ValidationResult::valid($other, 'OK');

        $this->expectException(InconsistentValidationResult::class);
        $result->assertConsistentWith($expected);
    }

    private function definition(
        ValidationLevel $level,
        string $ruleSetId,
        string $version,
    ): IdentifierDefinition {
        return new IdentifierDefinition(
            TestIdentifierTypeValidator::definitionFor()->key(),
            'Alternative test identifier',
            IdentifierCategory::CUSTOM,
            new CanonicalFormat('The literal ASCII value OK.', CharacterSet::CUSTOM, [2]),
            $level,
            [ValidationCapability::CHARACTERS, ValidationCapability::LENGTH],
            new RuleSetMetadata(
                $ruleSetId,
                $version,
                [new RuleSource('Consistency test fixture', __FILE__)],
                new DateTimeImmutable('2026-07-11'),
            ),
        );
    }
}
