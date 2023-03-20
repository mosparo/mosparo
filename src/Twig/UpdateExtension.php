<?php

namespace Mosparo\Twig;

use Mosparo\Helper\UpdateHelper;
use Symfony\Component\Security\Core\Security;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class UpdateExtension extends AbstractExtension implements GlobalsInterface
{
    protected Security $security;

    protected UpdateHelper $updateHelper;

    protected Environment $twig;

    protected bool $updatesEnabled = false;

    protected bool $automaticUpdateCheckEnabled = false;

    public function __construct(Security $security, UpdateHelper $updateHelper, Environment $twig, bool $updatesEnabled, bool $automaticUpdateCheckEnabled)
    {
        $this->security = $security;
        $this->updateHelper = $updateHelper;
        $this->twig = $twig;
        $this->updatesEnabled = $updatesEnabled;
        $this->automaticUpdateCheckEnabled = $automaticUpdateCheckEnabled;
    }

    public function getGlobals(): array
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            return [];
        }

        if (!$this->updatesEnabled) {
            return [
                'updatesEnabled' => false
            ];
        }

        $checkForUpdates = false;
        if (!$this->updateHelper->hasCachedData() && $this->automaticUpdateCheckEnabled) {
            $checkForUpdates = true;
        }

        return [
            'updatesEnabled' => true,
            'isUpdateAvailable' => $this->updateHelper->isUpdateAvailable($checkForUpdates),
        ];
    }
}