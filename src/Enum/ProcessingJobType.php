<?php

namespace Mosparo\Enum;

enum ProcessingJobType: int
{
    case UNKNOWN = 0;
    case UPDATE_CACHE = 1;
    case DELETE_RULE_PACKAGE = 2;
}