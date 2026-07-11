<?php

declare(strict_types=1);

namespace IdentiSpec\Enum;

enum ValidationStatus: string
{
    case VALID = 'VALID';
    case INVALID = 'INVALID';
    case UNSUPPORTED = 'UNSUPPORTED';
}
