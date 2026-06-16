<?php

namespace Mosparo\Enum;

enum RulePackageResult: int
{
    case COMPLETED = 0;
    case UNFINISHED = 1;
    case UNKNOWN_ERROR = 2;
    case ALREADY_UP_TO_DATE = 3;
    case NOT_AN_AUTOMATIC_TYPE = 4;
    case TOO_SOON_AFTER_THE_LAST_UPDATE = 5;
}