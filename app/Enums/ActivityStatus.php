<?php

namespace App\Enums;

enum ActivityStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
