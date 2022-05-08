<?php

namespace Mosparo\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mosparo\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Scheb\TwoFactorBundle\Model\BackupCodeInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User implements UserInterface, TwoFactorInterface, BackupCodeInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\Column(name="googleAuthenticatorSecret", type="string", nullable=true)
     */
    private $googleAuthenticatorSecret;

    /**
     * @ORM\Column(type="encryptedJson")
     */
    private $backupCodes = [];

    /**
     * @ORM\OneToMany(targetEntity=ProjectMember::class, mappedBy="user")
     */
    private $projectMemberships;

    /**
     * @ORM\Column(type="encryptedJson")
     */
    private $configValues = [];

    public function __construct()
    {
        $this->projectMemberships = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        return array_unique($this->roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function hasRole($roleKey): bool
    {
        return in_array($roleKey, $this->roles);
    }

    public function addRole($roleKey): bool
    {
        if ($this->hasRole($roleKey)) {
            return false;
        }

        $this->roles[] = $roleKey;

        return true;
    }

    public function removeRole($roleKey): bool
    {
        if (!$this->hasRole($roleKey)) {
            return false;
        }

        $index = array_search($roleKey, $this->roles);
        array_splice($this->roles, $index, 1);

        return true;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isGoogleAuthenticatorEnabled(): bool
    {
        return !empty($this->googleAuthenticatorSecret);
    }

    public function getGoogleAuthenticatorUsername(): string
    {
        return $this->email;
    }

    public function getGoogleAuthenticatorSecret(): ?string
    {
        return $this->googleAuthenticatorSecret;
    }

    public function setGoogleAuthenticatorSecret(?string $googleAuthenticatorSecret): void
    {
        $this->googleAuthenticatorSecret = $googleAuthenticatorSecret;
    }

    /**
     * Check if it is a valid backup code.
     *
     * @param string $code
     *
     * @return bool
     */
    public function isBackupCode(string $code): bool
    {
        return in_array($code, $this->backupCodes);
    }

    /**
     * Invalidate a backup code
     *
     * @param string $code
     */
    public function invalidateBackupCode(string $code): void
    {
        $key = array_search($code, $this->backupCodes);
        if ($key !== false){
            unset($this->backupCodes[$key]);
        }
    }

    /**
     * Add a backup code
     *
     * @param string $backUpCode
     */
    public function addBackupCode(string $backUpCode): void
    {
        if (!in_array($backUpCode, $this->backupCodes)) {
            $this->backupCodes[] = $backUpCode;
        }
    }

    /**
     * Resets all backup codes
     */
    public function resetBackupCodes(): void
    {
        $this->backupCodes = [];
    }

    /**
     * @return Collection|ProjectMember[]
     */
    public function getProjectMemberships(): Collection
    {
        return $this->projectMemberships;
    }

    public function addProjectMembership(ProjectMember $projectMembership): self
    {
        if (!$this->projectMemberships->contains($projectMembership)) {
            $this->projectMemberships[] = $projectMembership;
            $projectMembership->setUser($this);
        }

        return $this;
    }

    public function removeProjectMembership(ProjectMember $projectMembership): self
    {
        if ($this->projectMemberships->removeElement($projectMembership)) {
            // set the owning side to null (unless already changed)
            if ($projectMembership->getUser() === $this) {
                $projectMembership->setUser(null);
            }
        }

        return $this;
    }

    public function getConfigValues(): ?array
    {
        return $this->configValues;
    }

    public function setConfigValues(array $configValues): self
    {
        $this->configValues = $configValues;

        return $this;
    }

    public function getConfigValue($key)
    {
        if (!isset($this->configValues[$key])) {
            return null;
        }

        return $this->configValues[$key];
    }

    public function setConfigValue($key, $value): self
    {
        $this->configValues[$key] = $value;

        return $this;
    }
}
