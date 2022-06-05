<?php

namespace Mosparo\Entity;

use DateTimeInterface;
use Mosparo\Repository\SubmissionRepository;
use Doctrine\ORM\Mapping as ORM;
use Mosparo\Verification\GeneralVerification;

/**
 * @ORM\Entity(repositoryClass=SubmissionRepository::class)
 */
class Submission implements ProjectRelatedEntityInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=SubmitToken::class)
     */
    private $submitToken;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $validationToken;

    /**
     * @ORM\Column(type="encryptedJson")
     */
    private $data = [];

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $signature;

    /**
     * @ORM\Column(type="datetime")
     */
    private $submittedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $verifiedAt;

    /**
     * @ORM\Column(type="json")
     */
    private $matchedRuleItems = [];

    /**
     * @ORM\Column(type="json")
     */
    private $ignoredFields = [];

    /**
     * @ORM\Column(type="json")
     */
    private $generalVerifications = [];

    /**
     * @ORM\Column(type="float")
     */
    private $spamRating;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $project;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $spam;

    /**
     * @ORM\Column(type="float")
     */
    private $spamDetectionRating;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $valid;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubmitToken(): ?SubmitToken
    {
        return $this->submitToken;
    }

    public function setSubmitToken(?SubmitToken $submitToken): self
    {
        // unset the owning side of the relation if necessary
        if ($submitToken === null && $this->submitToken !== null && $this->submitToken->getSubmission() === $this) {
            $this->submitToken->setSubmission(null);
        }

        // set the owning side of the relation if necessary
        if ($submitToken !== null && $submitToken->getSubmission() !== $this) {
            $submitToken->setSubmission($this);
        }

        $this->submitToken = $submitToken;

        return $this;
    }

    public function getValidationToken(): ?string
    {
        return $this->validationToken;
    }

    public function setValidationToken(string $validationToken): self
    {
        $this->validationToken = $validationToken;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getSignature(): ?string
    {
        return $this->signature;
    }

    public function setSignature(string $signature): self
    {
        $this->signature = $signature;

        return $this;
    }

    public function getSubmittedAt(): ?DateTimeInterface
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(DateTimeInterface $submittedAt): self
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    public function getVerifiedAt(): ?DateTimeInterface
    {
        return $this->verifiedAt;
    }

    public function setVerifiedAt(DateTimeInterface $verifiedAt): self
    {
        $this->verifiedAt = $verifiedAt;

        return $this;
    }

    public function getMatchedRuleItems(): ?array
    {
        return $this->matchedRuleItems;
    }

    public function setMatchedRuleItems(array $matchedRuleItems): self
    {
        $this->matchedRuleItems = $matchedRuleItems;

        return $this;
    }

    public function getIgnoredFields(): ?array
    {
        return $this->ignoredFields;
    }

    public function setIgnoredFields(array $ignoredFields): self
    {
        $this->ignoredFields = $ignoredFields;

        return $this;
    }

    public function getGeneralVerifications(): array
    {
        $gvs = [];
        foreach ($this->generalVerifications as $key => $data) {
            $gvs[] = $this->getGeneralVerification($key);
        }

        return $gvs;
    }

    public function setGeneralVerifications(array $generalVerifications): self
    {
        foreach ($generalVerifications as $generalVerification) {
            $this->addGeneralVerification($generalVerification);
        }

        return $this;
    }

    public function addGeneralVerification(GeneralVerification $generalVerificiation): self
    {
        $this->generalVerifications[$generalVerificiation->getKey()] = [
            'valid' => $generalVerificiation->isValid(),
            'data' => $generalVerificiation->getData()
        ];

        return $this;
    }

    public function getGeneralVerification($key): ?GeneralVerification
    {
        if (!isset($this->generalVerifications[$key])) {
            return null;
        }

        $data = $this->generalVerifications[$key];

        return new GeneralVerification(
            $key,
            $data['valid'],
            $data['data']
        );
    }

    public function getSpamRating(): ?float
    {
        return $this->spamRating;
    }

    public function setSpamRating(float $spamRating): self
    {
        $this->spamRating = $spamRating;

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

    public function isSpam(): ?bool
    {
        return ($this->spam);
    }

    public function setSpam(?bool $spam): self
    {
        $this->spam = $spam;

        return $this;
    }

    public function getSpamDetectionRating(): ?float
    {
        return $this->spamDetectionRating;
    }

    public function setSpamDetectionRating(float $spamDetectionRating): self
    {
        $this->spamDetectionRating = $spamDetectionRating;

        return $this;
    }

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(?bool $valid): self
    {
        $this->valid = $valid;

        return $this;
    }
}