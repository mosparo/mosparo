<?php

namespace Mosparo\Security\Webauthn;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\User;
use Mosparo\Enum\AuthSource;
use Mosparo\Repository\WebauthnUserEntityRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Webauthn\Bundle\Exception\MissingUserEntityException;
use Webauthn\Bundle\Security\Guesser\UserEntityGuesser;
use Webauthn\PublicKeyCredentialUserEntity;

class CurrentUserEntityGuesser implements UserEntityGuesser
{
    protected Security $security;

    protected WebauthnUserEntityRepository $webauthnUserEntityRepository;

    protected EntityManagerInterface $entityManager;

    public function __construct(Security $security, WebauthnUserEntityRepository $webauthnUserEntityRepository, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->webauthnUserEntityRepository = $webauthnUserEntityRepository;
        $this->entityManager = $entityManager;
    }

    public function findUserEntity(Request $request): PublicKeyCredentialUserEntity
    {
        $user = $this->security->getUser();
        if (!($user instanceof User) || $user->getAuthSource() !== AuthSource::LOCAL) {
            throw MissingUserEntityException::create('User not logged in or is not a local user.');
        }

        if (!$user->getUserHandle()) {
            $randomHandle = bin2hex(random_bytes(16));
            $user->setUserHandle($randomHandle);

            $this->entityManager->flush();
        }

        $userEntity = $this->webauthnUserEntityRepository->findOneByUsername($user->getUserIdentifier());
        if (!$userEntity) {
            throw MissingUserEntityException::create('Unable to find the user entity');
        }

        return $userEntity;
    }
}
