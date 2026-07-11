<?php

declare(strict_types=1);

namespace IdentiSpec\Normalization\Internal;

use InvalidArgumentException;

/** @internal */
final readonly class AllowedSeparatorSet
{
    /** @var array<string, string> */
    private array $separators;

    /**
     * @param list<string> $separators
     */
    private function __construct(array $separators, bool $lettersAreCanonical)
    {
        $indexed = [];

        foreach ($separators as $separator) {
            if (strlen($separator) !== 1) {
                throw new InvalidArgumentException('Every separator must contain exactly one byte.');
            }

            if (self::isDigit($separator) || ($lettersAreCanonical && self::isLetter($separator))) {
                throw new InvalidArgumentException('A canonical character cannot be configured as a separator.');
            }

            $indexed[$separator] = $separator;
        }

        $this->separators = $indexed;
    }

    /** @param list<string> $separators */
    public static function forNumeric(array $separators): self
    {
        return new self($separators, false);
    }

    /** @param list<string> $separators */
    public static function forAlphanumeric(array $separators): self
    {
        return new self($separators, true);
    }

    public function contains(string $character): bool
    {
        return ($this->separators[$character] ?? null) === $character;
    }

    private static function isDigit(string $character): bool
    {
        return $character >= '0' && $character <= '9';
    }

    private static function isLetter(string $character): bool
    {
        return ($character >= 'A' && $character <= 'Z')
            || ($character >= 'a' && $character <= 'z');
    }
}
