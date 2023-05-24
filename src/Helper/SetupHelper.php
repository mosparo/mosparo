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

    protected string $projectDirectory;

    protected array $prerequisites = [
        'general' => [
            'minPhpVersion' => '7.4.0',
        ],
        'phpExtensions' => [
            'ctype' => true,
            'curl' => true,
            'dom' => true,
            'filter' => true,
            'gd' => true,
            'iconv' => true,
            'intl' => true,
            'json' => true,
            'libxml' => true,
            'openssl' => true,
            'pcre' => true,
            'pdo' => true,
            'pdo_mysql' => true,
            'simplexml' => true,
            'tokenizer' => true,
            'xml' => true,
            'zip' => true,
            'posix' => false,
            'sodium' => false,
            'Zend OPcache' => false,
        ],
        'writeAccess' => [
            '/config/env.mosparo.php' => true,
            '/public/resources/' => true,
            '/var/' => true,
        ]
    ];

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher, ConfigHelper $configHelper, TranslatorInterface $translator, string $projectDirectory)
    {
        $this->entityManager = $entityManager;
        $this->userPasswordHasher = $userPasswordHasher;
        $this->configHelper = $configHelper;
        $this->translator = $translator;
        $this->projectDirectory = $projectDirectory;
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

                        // Special check for the calculation bug with the method DateInterval::diff in PHP 8.1.0 - 8.1.9, fixed in 8.1.10
                        if (version_compare('8.1.0', PHP_VERSION, '<=') && version_compare('8.1.10', PHP_VERSION, '>')) {
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
            } else if ($type === 'writeAccess') {
                foreach ($prerequisites as $path => $isRequired) {
                    $fullPath = $this->projectDirectory . $path;
                    if (file_exists($fullPath)) {
                        $isWritable = is_writable($fullPath);
                    } else {
                        $parentPath = dirname($fullPath);
                        $isWritable = is_writable($parentPath);
                    }

                    if (!$isWritable) {
                        $meetPrerequisites = false;
                    }

                    $checkedPrerequisites[$type][$path] = [
                        'required' => $isRequired,
                        'available' => $path,
                        'pass' => $isWritable,
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