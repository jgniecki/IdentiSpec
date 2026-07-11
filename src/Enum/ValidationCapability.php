<?php

declare(strict_types=1);

namespace IdentiSpec\Enum;

enum ValidationCapability: string
{
    case CHARACTERS = 'CHARACTERS';
    case LENGTH = 'LENGTH';
    case STRUCTURE = 'STRUCTURE';
    case PREFIX = 'PREFIX';
    case CHECKSUM = 'CHECKSUM';
    case EMBEDDED_SEMANTICS = 'EMBEDDED_SEMANTICS';
}
