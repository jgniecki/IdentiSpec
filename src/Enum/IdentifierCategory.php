<?php

declare(strict_types=1);

namespace IdentiSpec\Enum;

enum IdentifierCategory: string
{
    case TAX = 'TAX';
    case VAT = 'VAT';
    case BUSINESS_REGISTER = 'BUSINESS_REGISTER';
    case PERSONAL_ID = 'PERSONAL_ID';
    case SOCIAL_SECURITY = 'SOCIAL_SECURITY';
    case CUSTOM = 'CUSTOM';
}
