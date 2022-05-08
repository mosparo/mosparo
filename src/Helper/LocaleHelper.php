<?php

namespace Mosparo\Helper;

use DateTimeZone;
use DirectoryIterator;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Symfony\Component\Intl\Locales;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocaleHelper
{
    protected $translator;

    protected $translationsDirectory;

    protected static $dateFormats = [
        'Y-m-d',
        'm/d/Y',
        'd.m.Y',
        'd-m-Y',
    ];

    protected static $timeFormats = [
        'H:i:s',
        'h:i:s A'
    ];

    public function __construct(TranslatorInterface $translator, $projectDirectory)
    {
        $this->translator = $translator;
        $this->translationsDirectory = $projectDirectory . '/translations';
    }

    public function storeUserSettingsInSession(Session $session, UserInterface $user)
    {
        $session->set('userLocale', $user->getConfigValue('locale'));
        $session->set('userDateFormat', $user->getConfigValue('dateFormat'));
        $session->set('userTimeFormat', $user->getConfigValue('timeFormat'));
        $session->set('userTimezone', $user->getConfigValue('timezone'));
    }

    public function findAvailableLanguages($withDefaultOption = false): array
    {
        $languages = [];
        if ($withDefaultOption) {
            $languages[$this->translator->trans('form.choices.systemDefault', [], 'mosparo')] = 'default';
            $languages[$this->translator->trans('form.choices.browserLanguage', [], 'mosparo')] = 'browser';
        }

        $dirIterator = new DirectoryIterator($this->translationsDirectory);
        foreach ($dirIterator as $file) {
            if ($file->isDot()) {
                continue;
            }

            $nameParts = explode('.', $file->getFilename());
            if (count($nameParts) != 3) {
                // Not the correct file name format
                continue;
            }

            if ($nameParts[0] === 'mosparo') {
                try {
                    $name = Locales::getName($nameParts[1]);
                } catch (MissingResourceException $exception) {
                    $name = $nameParts[1];
                }

                $languages[$name] = $nameParts[1];
            }
        }

        return $languages;
    }

    public function getDateFormats($withDefaultOption = false): array
    {
        $preparedDateFormats = [];
        if ($withDefaultOption) {
            $preparedDateFormats[$this->translator->trans('form.choices.systemDefault', [], 'mosparo')] = 'default';
        }

        foreach (self::$dateFormats as $dateFormat) {
            $label = sprintf('%s (%s)', date($dateFormat), $this->translateFormat($dateFormat));
            $preparedDateFormats[$label] = $dateFormat;
        }

        return $preparedDateFormats;
    }

    public function getTimeFormats($withDefaultOption = false): array
    {
        $preparedTimeFormats = [];
        if ($withDefaultOption) {
            $preparedTimeFormats[$this->translator->trans('form.choices.systemDefault', [], 'mosparo')] = 'default';
        }

        foreach (self::$timeFormats as $timeFormat) {
            $label = sprintf('%s (%s)', date($timeFormat), $this->translateFormat($timeFormat));
            $preparedTimeFormats[$label] = $timeFormat;
        }

        return $preparedTimeFormats;
    }

    public function getTimezones($withDefaultOption = false): array
    {
        $preparedTimezones = [];
        if ($withDefaultOption) {
            $preparedTimezones[$this->translator->trans('form.choices.systemDefault', [], 'mosparo')] = 'default';
        }


        foreach (DateTimeZone::listIdentifiers() as $timezone) {
            $preparedTimezones[$timezone] = $timezone;
        }

        return $preparedTimezones;
    }

    protected function translateFormat($format)
    {
        $values = [
            'Y' => 'YYYY',
            'm' => 'MM',
            'd' => 'DD',
            'h' => 'hh',
            'H' => 'hh',
            'i' => 'mm',
            's' => 'ss',
            'A' => 'AM/PM'
        ];

        foreach ($values as $formatChar => $translatedChar) {
            $format = str_replace($formatChar, $translatedChar, $format);
        }

        return $format;
    }
}