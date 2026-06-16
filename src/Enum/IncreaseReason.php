<?php

namespace Mosparo\Enum;

enum IncreaseReason: int
{
    case SPAM = 1;
    case VALID = 2;
    case DELAYED = 3;
    case BLOCKED = 4;
}