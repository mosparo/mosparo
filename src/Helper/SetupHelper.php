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
            'minPhpVersion' => '8.1.10',
        ],
        'phpExtension' => [
            'ctype' => true,
            'curl' => false,
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
            'pdo_mysql|pdo_pgsql|pdo_sqlite' => true,
            'posix' => false,
            'simplexml' => true,
            'sodium' => false,
            'tokenizer' => true,
            'xml' => true,
            'Zend OPcache' => false,
            'zip' => true,
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

    public function isInstalled(): bool
    {
        return $this->configHelper->getEnvironmentConfigValue('mosparo_installed', false);
    }

    public function checkPrerequisites($fullPrerequisites = null): array
    {
        if ($fullPrerequisites === null) {
            $fullPrerequisites = $this->prerequisites;
        }

        $meetPrerequisites = true;
        $checkedPrerequisites = [];

        foreach ($fullPrerequisites as $type => $prerequisites) {
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
                            'available' => str_replace(PHP_EXTRA_VERSION, '', PHP_VERSION),
                            'pass' => $result
                        ];
                    }
                }
            } else if ($type === 'phpExtension') {
                foreach ($prerequisites as $extensionKey => $isRequired) {
                    if (str_contains($extensionKey, '|')) {
                        $subExtensions = explode('|', $extensionKey);
                    } else {
                        $subExtensions = [$extensionKey];
                    }

                    $hasMultiple = false;
                    $oneAvailable = false;
                    if (count($subExtensions) > 1) {
                        $hasMultiple = true;
                        foreach ($subExtensions as $subExtensionKey) {
                            $version = phpversion($subExtensionKey);

                            if ($version) {
                                $oneAvailable = true;
                                break;
                            }
                        }
                    }

                    $isExtensionAvailable = false;
                    foreach ($subExtensions as $subExtensionKey) {
                        $version = phpversion($subExtensionKey);

                        if ($version) {
                            $isExtensionAvailable = true;
                        }

                        $checkedPrerequisites[$type][$subExtensionKey] = [
                            'required' => ($hasMultiple && $oneAvailable && $version == '') ? false : $isRequired,
                            'available' => ($version != ''),
                            'pass' => ($version != ''),
                        ];
                    }

                    if (!$isExtensionAvailable && $isRequired) {
                        $meetPrerequisites = false;
                    }
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

    public function checkUpgradePrerequisites($majorVersionData): array
    {
        $prerequisites = [];
        foreach ($majorVersionData['requirements'] as $requirement) {
            $type = $requirement['type'];
            $name = $requirement['name'];
            $required = $requirement['required'];
            $minValue = $requirement['minValue'] ?? null;

            if (!isset($prerequisites[$type]) || !is_array($prerequisites[$type])) {
                $prerequisites[$type] = [];
            }

            $prerequisites[$type][$name] = ($minValue !== null) ? $minValue : $required;
        }

        if (isset($prerequisites['phpExtension']['pdo_mysql']) && isset($prerequisites['phpExtension']['pdo_pgsql']) && isset($prerequisites['phpExtension']['pdo_sqlite'])) {
            $prerequisites['phpExtension']['pdo_mysql|pdo_pgsql|pdo_sqlite'] = true;

            unset($prerequisites['phpExtension']['pdo_mysql']);
            unset($prerequisites['phpExtension']['pdo_pgsql']);
            unset($prerequisites['phpExtension']['pdo_sqlite']);
        }

        ksort($prerequisites['phpExtension'], SORT_NATURAL | SORT_FLAG_CASE);

        return $this->checkPrerequisites($prerequisites);
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

        $users = $repository->findAll();
        $hasAdminUser = false;
        foreach ($users as $user) {
            if ($user->hasRole('ROLE_ADMIN')) {
                $hasAdminUser = true;
                break;
            }
        }
        if ($hasAdminUser) {
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
        foreach ($this->prerequisites['phpExtension'] as $extension => $isRequired) {
            if (str_contains($extension, '|')) {
                $subExtensions = explode('|', $extension);
            } else {
                $subExtensions = [$extension];
            }

            foreach ($subExtensions as $subExtension) {
                $versionNumber = phpversion($subExtension);

                if (!$versionNumber) {
                    $versionNumber = null;
                }

                $data[$subExtension] = $versionNumber;
            }
        }

        return $data;
    }

    public function hasPhpExtension($key): bool
    {
        $version = phpversion($key);

        return ($version !== false);
    }
}