<?php

declare(strict_types=1);

namespace IdentiSpec\Normalization\Internal;

/** @internal */
final class PrintableCharacter
{
    private function __construct() {}

    public static function fromByte(string $character): string
    {
        return match ($character) {
            ' ' => 'SPACE',
            "\t" => 'TAB',
            "\n" => 'LF',
            "\r" => 'CR',
            default => ord($character) >= 32 && ord($character) <= 126
                ? $character
                : sprintf('0x%02X', ord($character)),
        };
    }
}
