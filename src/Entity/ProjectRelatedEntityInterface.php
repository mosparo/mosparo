<?php

namespace Mosparo\Entity;

interface ProjectRelatedEntityInterface
{
    public function getProject(): ?Project;
    public function setProject(?Project $project): self;
}