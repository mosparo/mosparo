<?php

namespace Mosparo\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\User;
use Mosparo\Exception\AdminUserAlreadyExistsException;
use Mosparo\Exception\UserAlreadyExistsException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SetupHelper
{
    protected EntityManagerInterface $entityManager;

    protected UserPasswordHasherInterface $userPasswordHasher;

    protected ConfigHelper $configHelper;

    protected TranslatorInterface $translator;

    protected array $prerequisites = [
        'general' => [
            'minPhpVersion' => '8.1.10',
        ],
        'phpExtensions' => [
            'ctype' => true,
            'gd' => true,
            'iconv' => true,
            'intl' => true,
            'json' => true,
            'pdo' => true,
            'pdo_mysql' => true,
            'openssl' => true,
            'zip' => true,
            'posix' => false,
            'sodium' => false,
            'Zend OPcache' => false,
            'curl' => false,
        ],
    ];

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher, ConfigHelper $configHelper, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->userPasswordHasher = $userPasswordHasher;
        $this->configHelper = $configHelper;
        $this->translator = $translator;
    }

    public function checkPrerequisites(): array
    {
        $meetPrerequisites = true;
        $checkedPrerequisites = [];

        foreach ($this->prerequisites as $type => $prerequisites) {
            if (!isset($checkedPrerequisites[$type])) {
                $checkedPrerequisites[$type] = [];
            }

            if ($type === 'general') {
                foreach ($prerequisites as $subtype => $prerequisite) {
                    if ($subtype === 'minPhpVersion') {
                        $result = true;
                        if (!version_compare($prerequisite, PHP_VERSION, '<=')) {
                            $result = false;
                            $meetPrerequisites = false;
                        }

                        $checkedPrerequisites[$type][$subtype] = [
                            'required' => $prerequisite,
                            'available' => PHP_VERSION,
                            'pass' => $result
                        ];
                    }
                }
            } else if ($type === 'phpExtensions') {
                foreach ($prerequisites as $extensionKey => $isRequired) {
                    $version = phpversion($extensionKey);
                    if ($version == '' && $isRequired) {
                        $meetPrerequisites = false;
                    }

                    $checkedPrerequisites[$type][$extensionKey] = [
                        'required' => $isRequired,
                        'available' => ($version != ''),
                        'pass' => ($version != ''),
                    ];
                }
            }
        }

        return [ $meetPrerequisites, $checkedPrerequisites ];
    }

    public function generateEncryptionKey(): string
    {
        return sodium_bin2hex(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
    }

    public function createUser($emailAddress, $password): bool
    {
        $repository = $this->entityManager->getRepository(User::class);
        $user = $repository->findOneBy(['email' => $emailAddress]);
        if ($user !== null) {
            throw new UserAlreadyExistsException('User "' . $emailAddress . '" already exists.');
        }

        $qb = $repository->createQueryBuilder('u');
        $qb->select('u.id')
            ->where('u.roles LIKE :role')
            ->setParameter(':role', '%"ROLE_ADMIN"%');
        $adminUsers = $qb->getQuery()->getResult();
        if (!empty($adminUsers)) {
            throw new AdminUserAlreadyExistsException('An admin user exists already.');
        }

        $user = new User();
        $user->setEmail($emailAddress);
        $user->setPassword($this->userPasswordHasher->hashPassword(
            $user,
            $password
        ));

        $user->addRole('ROLE_USER');
        $user->addRole('ROLE_ADMIN');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return true;
    }

    public function getExtensionsData(): array
    {
        $data = [];
        foreach ($this->prerequisites['phpExtensions'] as $extension => $isRequired) {
            $versionNumber = phpversion($extension);
            if (!$versionNumber) {
                $versionNumber = null;
            }

            $data[$extension] = $versionNumber;
        }

        return $data;
    }
}