<?php

namespace App\Enums;

enum WorkItemStatus: string
{
    case New = 'new';
    case Assigned = 'assigned';
    case UnderAnalysis = 'under_analysis';
    case WaitingForCustomer = 'waiting_for_customer';
    case WaitingForThirdParty = 'waiting_for_third_party';
    case InDevelopment = 'in_development';
    case InTesting = 'in_testing';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
