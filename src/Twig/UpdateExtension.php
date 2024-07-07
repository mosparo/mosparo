<?php

namespace Mosparo\Twig;

use Mosparo\Helper\UpdateHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class UpdateExtension extends AbstractExtension implements GlobalsInterface
{
    protected Security $security;

    protected UpdateHelper $updateHelper;

    protected Environment $twig;

    protected RequestStack $requestStack;

    protected bool $updatesEnabled = false;

    protected bool $automaticUpdateCheckEnabled = false;

    public function __construct(Security $security, UpdateHelper $updateHelper, Environment $twig, RequestStack $requestStack, bool $updatesEnabled, bool $automaticUpdateCheckEnabled)
    {
        $this->security = $security;
        $this->updateHelper = $updateHelper;
        $this->twig = $twig;
        $this->requestStack = $requestStack;
        $this->updatesEnabled = $updatesEnabled;
        $this->automaticUpdateCheckEnabled = $automaticUpdateCheckEnabled;
    }

    public function getGlobals(): array
    {
        $request = $this->requestStack->getMainRequest();
        $route = $request->attributes->get('_route');
        if (!$this->security->isGranted('ROLE_ADMIN') || $route === 'administration_update_finalize') {
            return [];
        }

        if (!$this->automaticUpdateCheckEnabled ) {
            return [
                'updatesEnabled' => $this->updatesEnabled,
                'isUpdateAvailable' => false,
            ];
        }

        $checkForUpdates = false;
        if (!$this->updateHelper->hasCachedData() && $this->automaticUpdateCheckEnabled) {
            $checkForUpdates = true;
        }

        $isUpdateAvailable = false;
        try {
            $isUpdateAvailable = ($this->updateHelper->isUpdateAvailable($checkForUpdates) || $this->updateHelper->isUpgradeAvailable($checkForUpdates));
        } catch (\Exception $e) {
            // Ignore all exceptions and do nothing
        }

        return [
            'updatesEnabled' => $this->updatesEnabled,
            'isUpdateAvailable' => $isUpdateAvailable,
        ];
    }
}