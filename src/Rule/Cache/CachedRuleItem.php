<?php

namespace Mosparo\Rule\Cache;

use Mosparo\Rule\RuleItemEntityInterface;

class CachedRuleItem implements RuleItemEntityInterface
{
    protected string $uuid;

    protected string $type;

    protected string $value;

    protected ?float $spamRatingFactor;

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): RuleItemEntityInterface
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): RuleItemEntityInterface
    {
        $this->type = $type;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): RuleItemEntityInterface
    {
        $this->value = $value;

        return $this;
    }

    public function getSpamRatingFactor(): ?float
    {
        return $this->spamRatingFactor;
    }

    public function setSpamRatingFactor(?float $spamRatingFactor): RuleItemEntityInterface
    {
        $this->spamRatingFactor = $spamRatingFactor;

        return $this;
    }
}