<?php

namespace Mosparo\Rule\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mosparo\Rule\RuleEntityInterface;

class CachedRule implements RuleEntityInterface
{
    protected string $uuid;

    protected ?string $name;

    protected ?string $description;

    protected string $type;

    protected Collection $items;

    protected ?float $spamRatingFactor;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): RuleEntityInterface
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): RuleEntityInterface
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): RuleEntityInterface
    {
        $this->description = $description;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): RuleEntityInterface
    {
        $this->type = $type;

        return $this;
    }

    public function getItems(): ?Collection
    {
        return $this->items;
    }

    public function getSpamRatingFactor(): ?float
    {
        return $this->spamRatingFactor;
    }

    public function setSpamRatingFactor(?float $spamRatingFactor): RuleEntityInterface
    {
        $this->spamRatingFactor = $spamRatingFactor;

        return $this;
    }
}