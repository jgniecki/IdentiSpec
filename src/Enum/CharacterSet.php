<?php

declare(strict_types=1);

namespace IdentiSpec\Enum;

enum CharacterSet: string
{
    case DIGITS = 'DIGITS';
    case ASCII_ALPHANUMERIC = 'ASCII_ALPHANUMERIC';
    case CUSTOM = 'CUSTOM';
}
