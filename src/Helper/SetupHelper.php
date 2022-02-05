<?php

namespace Mosparo\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\User;
use Mosparo\Exception\UserAlreadyExistsException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class SetupHelper
{
    protected $entityManager;

    protected $passwordEncoder;

    protected $kernelRootDirectory;

    protected $prerequisites = [
        'general' => [
            'minPhpVersion' => '7.4.0',
        ],
        'phpExtensions' => [
            'ctype',
            'iconv',
            'intl',
            'json',
            'sqlite3',
            'pdo_mysql',
            'sodium',
        ],
    ];

    public function __construct(EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder, $kernelRootDirectory)
    {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->kernelRootDirectory = $kernelRootDirectory;
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
                foreach ($prerequisites as $extensionKey) {
                    $version = phpversion($extensionKey);
                    if ($version == '') {
                        $meetPrerequisites = false;
                    }

                    $checkedPrerequisites[$type][$extensionKey] = [
                        'required' => true,
                        'available' => ($version != ''),
                        'pass' => ($version != '')
                    ];
                }
            }
        }

        return [ $meetPrerequisites, $checkedPrerequisites ];
    }

    public function saveEnvLocal($values)
    {
        $lines = [];
        foreach ($values as $key => $value) {
            $lines[] = $key . '="' . addslashes($value) . '"';
        }

        $content = implode(PHP_EOL, $lines);

        $path = $this->kernelRootDirectory . '/.env.local';

        $filesystem = new Filesystem();
        $filesystem->dumpFile($path, $content);
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

        $user = new User();
        $user->setEmail($emailAddress);
        $user->setPassword($this->passwordEncoder->encodePassword(
            $user,
            $password
        ));

        $user->addRole('ROLE_USER');
        $user->addRole('ROLE_ADMIN');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return true;
    }
}