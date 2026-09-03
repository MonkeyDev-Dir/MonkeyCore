<?php

namespace App\Enums;

enum WorkItemOrigin: string
{
    case Internal = 'internal';
    case Api = 'api';
    case Portal = 'portal';
    case Email = 'email';
}
