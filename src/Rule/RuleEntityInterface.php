<?php

namespace Mosparo\Rule;

interface RuleEntityInterface
{
    public function getUuid(): ?string;
    public function setUuid(string $uuid): self;
    public function getName(): ?string;
    public function setName(string $name): self;
    public function getDescription(): ?string;
    public function setDescription(?string $description): self;
    public function getType(): ?string;
    public function setType(string $type): self;
    public function getItems(): ?array;
    public function setItems(?array $items): self;
    public function getSpamRatingFactor(): ?float;
    public function setSpamRatingFactor(?float $spamRatingFactor): self;
}