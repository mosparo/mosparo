<?php

namespace Mosparo\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(options: ['engine' => 'InnoDB'])]
#[ORM\Entity()]
class DetectionResult implements ProjectRelatedEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\OneToOne(targetEntity: Submission::class, inversedBy: 'detectionResult')]
    private ?Submission $submission;

    #[ORM\Column(type: 'json')]
    private array $matchedFieldRuleItems = [];

    #[ORM\Column(type: 'json')]
    private array $matchedSubmissionRules = [];

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubmission(): ?Submission
    {
        return $this->submission;
    }

    public function setSubmission(Submission $submission): self
    {
        $this->submission = $submission;

        return $this;
    }

    public function getMatchedFieldRuleItems(): ?array
    {
        return $this->matchedFieldRuleItems;
    }

    public function setMatchedFieldRuleItems(array $matchedFieldRuleItems): self
    {
        $this->matchedFieldRuleItems = $matchedFieldRuleItems;

        return $this;
    }

    public function addMatchedFieldRuleItem(string $path, array $result): void
    {
        if (!isset($this->matchedFieldRuleItems[$path])) {
            $this->matchedFieldRuleItems[$path] = [];
        }

        $this->matchedFieldRuleItems[$path][] = $result;
    }

    public function getMatchedSubmissionRules(): ?array
    {
        return $this->matchedSubmissionRules;
    }

    public function setMatchedSubmissionRules(array $matchedSubmissionRules): self
    {
        $this->matchedSubmissionRules = $matchedSubmissionRules;

        return $this;
    }

    public function addMatchedSubmissionRule(string $key, array $result): void
    {
        dump($result);
        $this->matchedSubmissionRules[$key] = $result;
    }

    public function countPoints(): float
    {
        $score = 0.0;
        foreach ($this->matchedFieldRuleItems as $issues) {
            foreach ($issues as $issue) {
                $score += $issue['rating'];
            }
        }

        foreach ($this->matchedSubmissionRules as $issue) {
            $score += $issue['rating'];
        }

        return $score;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }
}
