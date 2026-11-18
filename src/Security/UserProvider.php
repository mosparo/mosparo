<?php

namespace Mosparo\Security;

use Doctrine\ORM\EntityManagerInterface;
use Drenso\OidcBundle\Model\OidcTokens;
use Drenso\OidcBundle\Model\OidcUserData;
use Drenso\OidcBundle\Security\UserProvider\OidcUserProviderInterface;
use LogicException;
use Mosparo\Entity\User;
use Mosparo\Security\Oidc\OidcManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface, OidcUserProviderInterface, PasswordUpgraderInterface
{
    protected EntityManagerInterface $entityManager;

    protected RequestStack $requestStack;

    protected OidcManager $oidcManager;

    protected LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack, OidcManager $oidcManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->oidcManager = $oidcManager;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $identifier]);
        if (!$user) {
            throw new UserNotFoundException(sprintf('User with id "%s" not found', $identifier));
        }
        return $user;
    }

	/**
	 * @inheritDoc
	 */
	public function refreshUser(UserInterface $user): UserInterface
	{
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        $userRepository = $this->entityManager->getRepository(User::class);
        $refreshedUser = $userRepository->find($user->getId());
        if (!$refreshedUser) {
            throw new UserNotFoundException(sprintf('User with id "%s" not found', $user->getId()));
        }

        return $refreshedUser;
	}

	/**
	 * @inheritDoc
	 */
	public function supportsClass(string $class): bool
	{
        return User::class === $class || is_subclass_of($class, User::class);
	}

    public function ensureUserExists(string $userIdentifier, OidcUserData $userData, OidcTokens $tokens): void
    {
        $authMethod = $this->findAuthMethod();
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy([
            'userHandle' => $userIdentifier,
            'authMethod' => $authMethod,
        ]);

        $oidcClientConfig = $this->oidcManager->getOidcClientForAuthMethod($authMethod);
        if (!$oidcClientConfig) {
            throw new LogicException('No OIDC client was found for the given auth method.');
        }

        if (!$user) {
            if (!$userData->getEmail()) {
                throw new CustomUserMessageAuthenticationException('security.oidc.errorMessages.oidcDidNotReturnEmailAddress');
            }

            if (!$userData->getEmailVerified() && !$oidcClientConfig->isAssumeEmailVerified()) {
                throw new CustomUserMessageAuthenticationException('security.oidc.errorMessages.emailAddressNotVerified');
            }

            $user = $userRepository->findOneBy([
                'email' => $userData->getEmail(),
                'authMethod' => $authMethod,
                'userHandle' => null,
            ]);

            if ($user !== null && mb_strtolower($user->getEmail()) !== mb_strtolower($userData->getEmail())) {
                $user = null;
            }

            if ($user) {
                $user->setUserHandle($userIdentifier);
                $this->entityManager->flush();
            }
        }

        if (!$user || $user->getUserHandle() !== $userIdentifier) {
            $this->logger->error('OIDC login failed for user.', [
                'oidcClient' => $oidcClientConfig->getName(),
                'identifier' => $userIdentifier,
                'email' => $userData->getEmail(),
                'emailVerified' => $userData->getEmailVerified(),
            ]);
            throw new CustomUserMessageAuthenticationException('security.oidc.errorMessages.noUserFound');
        }
    }

    public function loadOidcUser(string $userIdentifier): UserInterface
    {
        $authMethod = $this->findAuthMethod();

        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy([
            'userHandle' => $userIdentifier,
            'authMethod' => $authMethod,
        ]);

        if (!$user) {
            throw new UserNotFoundException(sprintf('User with sub "%s" not found', $userIdentifier));
        }

        return $user;
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', get_debug_type($user)));
        }

        $userRepository = $this->entityManager->getRepository(User::class);
        if ($userRepository instanceof PasswordUpgraderInterface) {
            $userRepository->upgradePassword($user, $newHashedPassword);
        }
    }

    protected function findAuthMethod()
    {
        $request = $this->requestStack->getCurrentRequest();
        $currentRoute = $request->attributes->get('_route');
        $key = null;
        if ($currentRoute === 'login_oidc_check' && $request->query->has('code') && $request->query->has('state')) {
            $routeParams = $request->attributes->get('_route_params', []);
            $key = $this->oidcManager->getClientKeyForAlias($routeParams['alias'] ?? '');
        }
        return sprintf('oidc_%s', $key);
    }
}
