<?php

namespace Mosparo\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mosparo\Enum\TranslationKey;
use Mosparo\Repository\TranslationRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(options: ['engine' => 'InnoDB'])]
#[ORM\Entity(repositoryClass: TranslationRepository::class)]
#[ORM\Index(name: 't_ltk_idx', fields: ['project', 'translationKey', 'locale'])]
class Translation implements ProjectRelatedEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'integer', enumType: TranslationKey::class)]
    private TranslationKey $translationKey;
    
    #[ORM\Column(type: 'string', length: 8)]
    #[Assert\Regex('/^^[a-z]{2}(_([A-Z]{2}|[A-Za-z]{3,5}))?$/', message: 'translation.locale.invalidFormat')]
    private string $locale;
    
    #[ORM\Column(type: 'text')]
    private string $text;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTranslationKey(): TranslationKey
    {
        return $this->translationKey;
    }

    public function setTranslationKey(TranslationKey $translationKey): self
    {
        $this->translationKey = $translationKey;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

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

    public function toArray(): array
    {
        return [
            'translationKey' => $this->translationKey->value,
            'locale' => $this->locale,
            'text' => $this->text,
        ];
    }

    public function isEqual(array $translation): bool
    {
        if ($translation['locale'] !== $this->getLocale()) {
            return false;
        }

        if ($translation['translationKey'] !== $this->getTranslationKey()->value) {
            return false;
        }

        if ($translation['text'] !== $this->getText()) {
            return false;
        }

        return true;
    }
}
