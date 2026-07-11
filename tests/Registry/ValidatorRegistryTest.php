<?php

declare(strict_types=1);

namespace IdentiSpec\Tests\Registry;

use IdentiSpec\Exception\DuplicateIdentifierValidator;
use IdentiSpec\Registry\ValidatorRegistry;
use IdentiSpec\Tests\Fixture\TestIdentifierTypeValidator;
use IdentiSpec\Value\IdentifierKey;
use PHPUnit\Framework\TestCase;

final class ValidatorRegistryTest extends TestCase
{
    public function testFindsValidatorByCanonicalKey(): void
    {
        $validator = new TestIdentifierTypeValidator();
        $registry = new ValidatorRegistry([$validator]);

        self::assertSame($validator, $registry->find(IdentifierKey::fromParts('xx', 'test')));
        self::assertNull($registry->find(IdentifierKey::fromParts('YY', 'OTHER')));
    }

    public function testRejectsDuplicateKey(): void
    {
        $this->expectException(DuplicateIdentifierValidator::class);

        new ValidatorRegistry([
            new TestIdentifierTypeValidator(),
            new TestIdentifierTypeValidator('xx', 'test'),
        ]);
    }

    public function testListingIsIndependentOfRegistrationOrder(): void
    {
        $registry = new ValidatorRegistry([
            new TestIdentifierTypeValidator('ZZ', 'LAST'),
            new TestIdentifierTypeValidator('AA', 'FIRST'),
        ]);

        self::assertSame(
            ['AA:FIRST', 'ZZ:LAST'],
            array_map(
                static fn ($definition): string => $definition->key()->toString(),
                $registry->definitions(),
            ),
        );
    }
}
