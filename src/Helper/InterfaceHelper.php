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

    public function __construct(TranslatorInterface $translator, $defaultColorMode)
    {
        $this->translator = $translator;
        $this->defaultColorMode = $defaultColorMode;
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

    public function storeUserSettingsInSession(Session $session, User $user)
    {
        $session->set('userColorMode', $user->getConfigValue('colorMode'));
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
}