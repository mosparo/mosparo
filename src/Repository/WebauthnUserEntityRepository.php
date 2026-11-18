<?php

declare(strict_types=1);

namespace Mosparo\Repository;

use Mosparo\Entity\User;
use Mosparo\Enum\AuthSource;
use Webauthn\Bundle\Repository\PublicKeyCredentialUserEntityRepositoryInterface;
use Webauthn\PublicKeyCredentialUserEntity;

final class WebauthnUserEntityRepository implements PublicKeyCredentialUserEntityRepositoryInterface
{
    /**
     * The UserRepository $userRepository is the repository
     * that already exists in the application
     */
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function findOneByUsername(string $username): ?PublicKeyCredentialUserEntity
    {
        /** @var User|null $user */
        $user = $this->userRepository->findOneBy([
            'email' => $username,
            'authSource' => AuthSource::LOCAL,
        ]);

        if ($user && !$user->getUserHandle()) {
            return null;
        }

        return $this->getUserEntity($user);
    }

    public function findOneByUserHandle(string $userHandle): ?PublicKeyCredentialUserEntity
    {
        /** @var User|null $user */
        $user = $this->userRepository->findOneBy([
            'userHandle' => $userHandle,
            'authSource' => AuthSource::LOCAL,
        ]);

        return $this->getUserEntity($user);
    }

    /**
     * Converts a Symfony User (if any) into a WebAuthn User Entity
     */
    private function getUserEntity(null|User $user): ?PublicKeyCredentialUserEntity
    {
        if ($user === null) {
            return null;
        }

        return new PublicKeyCredentialUserEntity(
            $user->getUsername(),
            $user->getUserHandle(),
            $user->getUserIdentifier(),
            null
        );
    }
}
