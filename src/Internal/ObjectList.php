<?php

declare(strict_types=1);

namespace IdentiSpec\Internal;

use InvalidArgumentException;

/** @internal */
final class ObjectList
{
    private function __construct() {}

    /**
     * @template T of object
     *
     * @param class-string<T> $expectedClass
     *
     * @return list<T>
     */
    public static function normalize(
        mixed $values,
        string $expectedClass,
        bool $requireNonEmpty = false,
    ): array {
        if (!is_array($values) || !array_is_list($values)) {
            throw new InvalidArgumentException('Object collection must be a list.');
        }

        if ($requireNonEmpty && $values === []) {
            throw new InvalidArgumentException('Object collection cannot be empty.');
        }

        foreach ($values as $value) {
            if (!$value instanceof $expectedClass) {
                throw new InvalidArgumentException(sprintf(
                    'Every collection item must be an instance of %s.',
                    $expectedClass,
                ));
            }
        }

        /** @var list<T> $values */
        return $values;
    }
}
