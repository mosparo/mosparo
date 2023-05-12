<?php

namespace Mosparo\Helper;

use DateTimeZone;
use DirectoryIterator;
use Mosparo\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Symfony\Component\Intl\Locales;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocaleHelper
{
    protected TranslatorInterface $translator;

    protected string $translationsDirectory;

    protected string $defaultDateFormat;

    protected string $defaultTimeFormat;

    protected string $defaultTimezone;

    protected static array $dateFormats = [
        'Y-m-d',
        'm/d/Y',
        'd.m.Y',
        'd-m-Y',
    ];

    protected static array $timeFormats = [
        'H:i:s',
        'h:i:s A'
    ];

    public function __construct(TranslatorInterface $translator, $projectDirectory, $defaultDateFormat, $defaultTimeFormat, $defaultTimezone)
    {
        $this->translator = $translator;
        $this->translationsDirectory = $projectDirectory . '/translations';
        $this->defaultDateFormat = $defaultDateFormat;
        $this->defaultTimeFormat = $defaultTimeFormat;
        $this->defaultTimezone = $defaultTimezone;
    }

    /**
     * The Request from the Symfony HttpFoundation will return the preferred language in the format of
     * [a-z]_[A-Z]{2,}. ISO 15924 (https://en.wikipedia.org/wiki/ISO_15924) lists the names of the scripts like zh_Hans
     * with one uppercase character only. Weblate exports the translation files with the same script format.
     * Because of this, we have to fix the Symfony logic for the preferred language.
     *
     * If someone knows a better solution, please get in touch with us.
     *
     * @param string $locale
     * @return string
     */
    public function fixPreferredLanguage(string $locale): string
    {
        if (strpos($locale,'_') === false) {
            return $locale;
        }

        [ $languagePart, $scriptPart ] = explode('_', $locale);

        // To be compatible with the country and region script codes,
        // we can change the script only if it's longer than two characters.
        if (strlen($scriptPart) > 2) {
            $scriptPart = ucfirst(strtolower($scriptPart));
        }

        return $languagePart . '_' . $scriptPart;
    }

    public function determineLocaleValues(Request $request): array
    {
        $browserLocale = null;
        if (!empty($request->getPreferredLanguage())) {
            $browserLocale = $this->fixPreferredLanguage($request->getPreferredLanguage());
        }

        $locale = '';
        $dateFormat = $this->defaultDateFormat;
        $timeFormat = $this->defaultTimeFormat;
        $timezone = $this->defaultTimezone;

        $session = $request->getSession();
        if ($session !== null && $session->has('userLocale')) {
            $userLocale = $session->get('userLocale');

            if ($userLocale === 'browser' && $browserLocale !== null) {
                $locale = $browserLocale;
            } else if ($userLocale !== null && $userLocale !== 'default') {
                $locale = $userLocale;
            }

            $userDateFormat = $session->get('userDateFormat');
            if ($userDateFormat !== null && $userDateFormat !== 'default') {
                $dateFormat = $userDateFormat;
            }

            $userTimeFormat = $session->get('userTimeFormat');
            if ($userTimeFormat !== null && $userTimeFormat !== 'default') {
                $timeFormat = $userTimeFormat;
            }

            $userTimezone = $session->get('userTimezone');
            if ($userTimezone !== null && $userTimezone !== 'default') {
                $timezone = $userTimezone;
            }
        } else if ($browserLocale !== null) {
            $locale = $browserLocale;
        }

        return [$locale, $dateFormat, $timeFormat, $timezone];
    }

    public function storeUserSettingsInSession(Session $session, User $user)
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
                // If the translation file is empty, we do not offer it as available language.
                if (!$this->containsContent($file->getPathname())) {
                    continue;
                }

                try {
                    $name = Locales::getName($nameParts[1]);
                } catch (MissingResourceException $exception) {
                    $name = $nameParts[1];
                }

                $languages[$name] = $nameParts[1];
            }
        }

        ksort($languages);

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

    /**
     * Returns true if the given file contains content, false if not.
     *
     * @param string $filename
     * @return bool
     */
    protected function containsContent(string $filename): bool
    {
        $content = trim(file_get_contents($filename));
        $content = trim($content, '{}');

        return ($content != '');
    }
}