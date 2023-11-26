<?php

namespace Mosparo\Exception;

use Mosparo\Exception;

class ImportException extends Exception
{
    const JOB_DATA_FILE_NOT_FOUND = 1;
    const JOB_DATA_INVALID = 2;
    const IMPORT_FILE_NOT_FOUND = 3;
    const IMPORT_FILE_INVALID = 4;
    const PROJECT_NOT_AVAILABLE = 5;
    const WRONG_SPECIFICATIONS_VERSION = 6;
    const NO_CHANGES_AVAILABLE = 7;
    const STORED_RULE_NOT_FOUND = 8;
    const STORED_RULE_ITEM_NOT_FOUND = 9;
    const STORED_RULESET_NOT_FOUND = 10;
    const STORED_SECURITY_GUIDELINE_NOT_FOUND = 11;
}