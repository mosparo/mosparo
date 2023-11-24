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
    const SUBMISSION_FIELD_NOT_VERIFIED = 'not-verified';
    const SUBMISSION_FIELD_VALID = 'valid';
    const SUBMISSION_FIELD_INVALID = 'invalid';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\ManyToOne(targetEntity=SubmitToken::class)
     */
    private ?SubmitToken $submitToken;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private ?string $validationToken = null;

    /**
     * @ORM\Column(type="encryptedJson")
     */
    private array $data = [];

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private ?string $signature = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?DateTimeInterface $submittedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTimeInterface $verifiedAt = null;

    /**
     * @ORM\Column(type="json")
     */
    private array $matchedRuleItems = [];

    /**
     * @ORM\Column(type="json")
     */
    private array $ignoredFields = [];

    /**
     * @ORM\Column(type="json")
     */
    private array $verifiedFields = [];

    /**
     * @ORM\Column(type="json")
     */
    private array $generalVerifications = [];

    /**
     * @ORM\Column(type="float")
     */
    private ?float $spamRating;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Project $project;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $spam = null;

    /**
     * @ORM\Column(type="float")
     */
    private ?float $spamDetectionRating;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $valid = null;

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
        if ($submitToken === null && $this->submitToken !== null && $this->submitToken->getLastSubmission() === $this) {
            $this->submitToken->setLastSubmission(null);
        }

        // set the owning side of the relation if necessary
        if ($submitToken !== null && $submitToken->getLastSubmission() !== $this) {
            $submitToken->setLastSubmission($this);
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

    public function getDataValue(string $group, string $name)
    {
        if (!isset($this->data[$group])) {
            return false;
        }

        foreach ($this->data[$group] as $item) {
            if ($item['name'] === $name) {
                return $item['value'];
            }
        }

        return false;
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

    public function getVerifiedFields(): ?array
    {
        return $this->verifiedFields;
    }

    public function setVerifiedFields(array $verifiedFields): self
    {
        $this->verifiedFields = $verifiedFields;

        return $this;
    }

    public function setVerifiedField(string $key, string $value)
    {
        $this->verifiedFields[$key] = $value;
    }

    public function getVerifiedField(string $key): string
    {
        if (!isset($this->verifiedFields[$key])) {
            return self::SUBMISSION_FIELD_NOT_VERIFIED;
        }

        return $this->verifiedFields[$key];
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
