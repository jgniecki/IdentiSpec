<?php

declare(strict_types=1);

namespace IdentiSpec\Enum;

enum PrefixPolicy: string
{
    case NONE = 'NONE';
    case REQUIRED = 'REQUIRED';
    case OPTIONAL = 'OPTIONAL';
    case FORBIDDEN = 'FORBIDDEN';
}
