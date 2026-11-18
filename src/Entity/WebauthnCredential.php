<?php

declare(strict_types=1);

namespace Mosparo\Entity;

use Doctrine\DBAL\Types\Types;
use Mosparo\Repository\WebauthnCredentialRepository;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\Ulid;
use Webauthn\CredentialRecord;
use Webauthn\TrustPath\TrustPath;

#[Table(name: "webauthn_credentials")]
#[Entity(repositoryClass: WebauthnCredentialRepository::class)]
class WebauthnCredential extends CredentialRecord
{
    #[Id]
    #[Column(unique: true)]
    #[GeneratedValue(strategy: "NONE")]
    private string $id;

    #[Column(type: Types::STRING)]
    private string $name;

    public function __construct(
        string $name,
        string $publicKeyCredentialId,
        string $type,
        array $transports,
        string $attestationType,
        TrustPath $trustPath,
        AbstractUid $aaguid,
        string $credentialPublicKey,
        string $userHandle,
        int $counter
    )
    {
        $this->id = Ulid::generate();
        $this->name = $name;
        parent::__construct($publicKeyCredentialId, $type, $transports, $attestationType, $trustPath, $aaguid, $credentialPublicKey, $userHandle, $counter);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
