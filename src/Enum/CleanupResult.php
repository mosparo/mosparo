<?php

namespace Mosparo\Enum;

enum CleanupResult: int
{
    case COMPLETED = 0;
    case UNFINISHED = 1;
    case NEXT_CLEANUP_IN_THE_FUTURE = 2;
    case CLEANUP_RUNNING_ALREADY = 3;
    case UNFINISHED_CLEANUP_STATISTIC_OBJECT = 4;
    case CLEANUP_JUST_EXECUTED = 5;
}