<?php

namespace Mosparo\Twig;

use Mosparo\Rules\SubmissionRule\SubmissionRuleInterface;
use Mosparo\Rules\SubmissionRule\SubmissionRuleManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SubmissionExtension extends AbstractExtension
{
    protected SubmissionRuleManager $submissionRuleManager;

    public function __construct(SubmissionRuleManager $submissionRuleManager)
    {
        $this->submissionRuleManager = $submissionRuleManager;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_submission_rule', [$this, 'getSubmissionRule']),
        ];
    }

    public function getSubmissionRule(string $key): ?SubmissionRuleInterface
    {
        return $this->submissionRuleManager->getRule($key);
    }
}