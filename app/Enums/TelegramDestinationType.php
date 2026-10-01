<?php

namespace App\Enums;

enum TelegramDestinationType: string
{
case Private = 'private';
case Group = 'group';
case Supergroup = 'supergroup';
}
