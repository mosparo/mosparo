<?php

namespace Mosparo\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\User;
use Mosparo\Exception\AdminUserAlreadyExistsException;
use Mosparo\Exception\UserAlreadyExistsException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SetupHelper
{
    protected $entityManager;

    protected $passwordEncoder;

    protected $configHelper;

    protected $translator;

    protected $prerequisites = [
        'general' => [
            'minPhpVersion' => '7.4.0',
        ],
        'phpExtensions' => [
            'ctype' => true,
            'iconv' => true,
            'intl' => true,
            'json' => true,
            'pdo' => true,
            'pdo_mysql' => true,
            'sodium' => false,
            'Zend OPcache' => false,
        ],
    ];

    public function __construct(EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder, ConfigHelper $configHelper, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
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
            ->setParameter(':role', '%"ROLE_ADMIN2"%');
        $adminUsers = $qb->getQuery()->getResult();
        if (!empty($adminUsers)) {
            throw new AdminUserAlreadyExistsException('An admin user exists already.');
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

    public function getMailEncryptionOptions(): array
    {
        return [
            'setup.mail.form.options.encryption.none' => 'null',
            'setup.mail.form.options.encryption.tls' => 'tls',
            'setup.mail.form.options.encryption.ssl' => 'ssl'
        ];
    }
}