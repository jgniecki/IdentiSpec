<?php

declare(strict_types=1);

namespace IdentiSpec\Enum;

enum ValidationMode: string
{
    case STRICT = 'STRICT';
    case LENIENT = 'LENIENT';
}
