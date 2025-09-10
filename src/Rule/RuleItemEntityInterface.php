<?php

namespace Mosparo\Rule;

interface RuleItemEntityInterface
{
    public function getUuid(): ?string;
    public function setUuid(string $uuid): self;
    public function getType(): ?string;
    public function setType(string $type): self;
    public function getValue(): ?string;
    public function setValue(string $value): self;
    public function getSpamRatingFactor(): ?float;
    public function setSpamRatingFactor(?float $spamRatingFactor): self;
    public function getPreparedValue(): ?string;
    public function setPreparedValue(string $preparedValue): self;
    public function getHashedValue(): ?string;
    public function setHashedValue(string $hashedValue): self;
    public function getParent(): RuleEntityInterface;

}