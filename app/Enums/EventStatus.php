<?php

namespace App\Enums;

enum EventStatus: string
{
case Planned = 'planned';
case Confirmed = 'confirmed';
case Cancelled = 'cancelled';
case Completed = 'completed';
}
