<?php

declare(strict_types=1);

namespace IdentiSpec\Enum;

enum ValidationLevel: string
{
    case FORMAT_ONLY = 'FORMAT_ONLY';
    case FORMAT_AND_CHECKSUM = 'FORMAT_AND_CHECKSUM';
    case FULL_OFFLINE_RULES = 'FULL_OFFLINE_RULES';
}
