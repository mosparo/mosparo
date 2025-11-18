<?php

namespace Mosparo\Helper;

use Mosparo\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

class InterfaceHelper
{
    protected TranslatorInterface $translator;

    protected string $defaultColorMode;

    protected string $defaultNumberOfItemsPerPage;

    public function __construct(TranslatorInterface $translator, string $defaultColorMode, int $defaultNumberOfItemsPerPage)
    {
        $this->translator = $translator;
        $this->defaultColorMode = $defaultColorMode;
        $this->defaultNumberOfItemsPerPage = $defaultNumberOfItemsPerPage;
    }

    public function determineColorMode(Request $request): string
    {
        $colorMode = $this->defaultColorMode;

        $session = $request->getSession();
        if ($session !== null && $session->has('userColorMode')) {
            $userColorMode = $session->get('userColorMode');

            if ($userColorMode !== null && $userColorMode !== 'default') {
                $colorMode = $userColorMode;
            }
        }

        return $colorMode;
    }

    public function determineNumberOfItemsPerPage(Request $request): int
    {
        $numberOfItemsPerPage = $this->defaultNumberOfItemsPerPage;

        $session = $request->getSession();
        if ($session !== null && $session->has('numberOfItemsPerPage')) {
            $userNumberOfItemsPerPage = $session->get('numberOfItemsPerPage');

            if ($userNumberOfItemsPerPage !== null && $userNumberOfItemsPerPage !== 'default') {
                $numberOfItemsPerPage = $userNumberOfItemsPerPage;
            }
        }

        return $numberOfItemsPerPage;
    }

    public function storeUserSettingsInSession(Session $session, User $user)
    {
        $session->set('userColorMode', $user->getConfigValue('colorMode'));
        $session->set('numberOfItemsPerPage', $user->getConfigValue('numberOfItemsPerPage'));
    }

    public function getColorModes($withDefaultOption = false): array
    {
        $modes = [];
        if ($withDefaultOption) {
            $modes[$this->translator->trans('form.choices.systemDefault', [], 'mosparo')] = 'default';
        }

        $modes[$this->translator->trans('interface.colorModes.light', [], 'mosparo')] = 'light';
        $modes[$this->translator->trans('interface.colorModes.dark', [], 'mosparo')] = 'dark';

        return $modes;
    }

    public function getNumberOfItemsPerPageOptions($withDefaultOption = false): array
    {
        $options = [];
        if ($withDefaultOption) {
            $options[$this->translator->trans('form.choices.systemDefault', [], 'mosparo')] = 'default';
        }

        $options[10] = 10;
        $options[25] = 25;
        $options[50] = 50;
        $options[100] = 100;
        $options[250] = 250;
        $options[500] = 500;

        return $options;
    }
}