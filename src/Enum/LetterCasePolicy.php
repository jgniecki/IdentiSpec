<?php

declare(strict_types=1);

namespace IdentiSpec\Enum;

enum LetterCasePolicy: string
{
    case PRESERVE = 'PRESERVE';
    case UPPERCASE = 'UPPERCASE';
    case LOWERCASE = 'LOWERCASE';
}
