<?php

namespace Mosparo\RulePackage;

use Mosparo\Entity\RulePackageProcessingJob;
use Mosparo\Enum\RulePackageResult;

interface ImporterInterface
{
    public function importRulePackage(RulePackageProcessingJob $processingJob, string $filePath, string $cacheDirectory): RulePackageResult;
}