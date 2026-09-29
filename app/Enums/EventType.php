<?php

namespace App\Enums;

enum EventType: string
{
case Concert = 'concert';
case Meeting = 'meeting';
case Rehearsal = 'rehearsal';
case Other = 'other';
}
