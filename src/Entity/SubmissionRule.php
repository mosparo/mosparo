<?php

namespace Mosparo\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Mosparo\Repository\SubmissionRuleRepository;

#[ORM\Table(options: ['engine' => 'InnoDB'])]
#[ORM\Entity(repositoryClass: SubmissionRuleRepository::class)]
#[ORM\Index(name: 'sr_key_idx', fields: ['project', 'key'])]
class SubmissionRule implements ProjectRelatedEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'string', name: 'rule_key', length: 50)]
    private ?string $key;

    #[ORM\OneToMany(targetEntity: SubmissionRuleConfigValue::class, mappedBy: 'submissionRule', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $configValues;

    #[ORM\Column(type: 'boolean')]
    private bool $enabled = true;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $rating = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project;

    public function __construct()
    {
        $this->configValues = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function getConfigValues(): ?array
    {
        foreach ($this->configValues as $configValue) {
            $configValues[$configValue->getName()] = $configValue->getValue();
        }

        return $configValues;
    }

    public function setConfigValues(array $configValues): self
    {
        foreach ($configValues as $key => $value) {
            $this->setConfigValue($key, $value);
        }

        return $this;
    }

    public function getConfigValue($key): mixed
    {
        $configValue = $this->findConfigValue($key);
        if (!$configValue) {
            return null;
        }

        return $configValue->getValue();
    }

    public function setConfigValue($key, $value): self
    {
        $configValue = $this->findConfigValue($key);
        if (!$configValue) {
            $configValue = new SubmissionRuleConfigValue();
            $configValue->setName($key);
            $configValue->setSubmissionRule($this);
            $this->configValues->add($configValue);
        }

        $configValue->setValue($value);

        return $this;
    }

    protected function findConfigValue($key): ?SubmissionRuleConfigValue
    {
        $filteredConfigValues = $this->configValues->filter(function ($el) use ($key) {
            if ($el->getName() === $key) {
                return $el;
            }

            return false;
        });

        if ($filteredConfigValues->isEmpty()) {
            return null;
        }

        return $filteredConfigValues->first();
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function setRating(?float $rating): self
    {
        $this->rating = $rating;

        return $this;
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

    public function isEqual(array $rule): bool
    {
        if ($rule['enabled'] !== $this->isEnabled()) {
            return false;
        }

        if (!$this->areConfigValuesEqual($rule['configValues'])) {
            return false;
        }

        if (floatval($rule['rating']) !== $this->getRating()) {
            return false;
        }

        return true;
    }

    public function areConfigValuesEqual(array $otherConfigValues): bool
    {
        $configValues = $this->getConfigValues();

        ksort($configValues);
        ksort($otherConfigValues);

        return ($configValues === $otherConfigValues);
    }
}
