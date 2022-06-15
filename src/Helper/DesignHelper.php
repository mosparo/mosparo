<?php

namespace Mosparo\Helper;

use DirectoryIterator;
use Mosparo\Entity\Project;
use Mosparo\Util\HashUtil;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mime\MimeTypes;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupCollection;

class DesignHelper
{
    protected static array $designConfigValueKeys = [
        'boxSize',
        'boxRadius',
        'colorBackground',
        'colorBorder',
        'colorCheckbox',
        'colorText',
        'colorShadow',
        'colorShadowInset',
        'colorFocusCheckbox',
        'colorFocusCheckboxShadow',
        'colorLoadingCheckbox',
        'colorLoadingCheckboxAnimatedCircle',
        'colorSuccessBackground',
        'colorSuccessBorder',
        'colorSuccessCheckbox',
        'colorSuccessText',
        'colorSuccessShadow',
        'colorSuccessShadowInset',
        'colorFailureBackground',
        'colorFailureBorder',
        'colorFailureCheckbox',
        'colorFailureText',
        'colorFailureTextError',
        'colorFailureShadow',
        'colorFailureShadowInset',
        'showPingAnimation',
        'showMosparoLogo',
    ];

    protected static array $cssVariableNames = [
        '--mosparo-border-color' => ['key' => 'colorBorder', 'type' => 'color'],
        '--mosparo-border-radius' => ['key' => 'boxRadius', 'type' => 'number'],
        '--mosparo-background-color' => ['key' => 'colorBackground', 'type' => 'color'],
        '--mosparo-text-color' => ['key' => 'colorText', 'type' => 'color'],
        '--mosparo-shadow-color' => ['key' => 'colorShadow', 'type' => 'color'],
        '--mosparo-shadow-inset-color' => ['key' => 'colorShadowInset', 'type' => 'color'],
        '--mosparo-circle-border-color' => ['key' => 'colorCheckbox', 'type' => 'color'],
        '--mosparo-ping-animation-name' => ['key' => 'showPingAnimation', 'type' => 'bool'],
        '--mosparo-focus-circle-border-color' => ['key' => 'colorFocusCheckbox', 'type' => 'color'],
        '--mosparo-focus-circle-shadow-color' => ['key' => 'colorFocusCheckboxShadow', 'type' => 'color'],
        '--mosparo-loading-circle-border-color' => ['key' => 'colorLoadingCheckbox', 'type' => 'color'],
        '--mosparo-loading-circle-animated-border-color' => ['key' => 'colorLoadingCheckboxAnimatedCircle', 'type' => 'color'],
        '--mosparo-success-border-color' => ['key' => 'colorSuccessBorder', 'type' => 'color'],
        '--mosparo-success-background-color' => ['key' => 'colorSuccessBackground', 'type' => 'color'],
        '--mosparo-success-circle-border-color' => ['key' => 'colorSuccessCheckbox', 'type' => 'color'],
        '--mosparo-success-text-color' => ['key' => 'colorSuccessText', 'type' => 'color'],
        '--mosparo-success-shadow-color' => ['key' => 'colorSuccessShadow', 'type' => 'color'],
        '--mosparo-success-shadow-inset-color' => ['key' => 'colorSuccessShadowInset', 'type' => 'color'],
        '--mosparo-failure-border-color' => ['key' => 'colorFailureBorder', 'type' => 'color'],
        '--mosparo-failure-background-color' => ['key' => 'colorFailureBackground', 'type' => 'color'],
        '--mosparo-failure-circle-border-color' => ['key' => 'colorFailureCheckbox', 'type' => 'color'],
        '--mosparo-failure-text-color' => ['key' => 'colorFailureText', 'type' => 'color'],
        '--mosparo-failure-text-error-color' => ['key' => 'colorFailureTextError', 'type' => 'color'],
        '--mosparo-failure-shadow-color' => ['key' => 'colorFailureShadow', 'type' => 'color'],
        '--mosparo-failure-shadow-inset-color' => ['key' => 'colorFailureShadowInset', 'type' => 'color'],
        '--mosparo-show-logo' => ['key' => 'showMosparoLogo', 'type' => 'bool'],
    ];

    protected static array $boxSizeVariables = [
        'small' => [
            '--mosparo-font-size' => 12,
            '--mosparo-line-height' => 16,
            '--mosparo-padding-top' => 16,
            '--mosparo-padding-left' => 20,
            '--mosparo-padding-right' => 16,
            '--mosparo-padding-bottom' => 16,
            '--mosparo-border-radius' => 8,
            '--mosparo-border-width' => 2,
            '--mosparo-container-min-width' => 250,
            '--mosparo-container-max-width' => 430,
            '--mosparo-circle-size' => 32,
            '--mosparo-circle-radius' => 16,
            '--mosparo-circle-border-width' => 2,
            '--mosparo-circle-offset' => -2,
            '--mosparo-circle-margin-right' => 16,
            '--mosparo-shadow-blur-radius' => 8,
            '--mosparo-shadow-spread-radius' => 2,
            '--mosparo-icon-border-offset' => -1,
            '--mosparo-icon-border-width' => 2,
            '--mosparo-checkmark-icon-height' => 8,
            '--mosparo-logo-left' => 9,
            '--mosparo-logo-bottom' => 1,
            '--mosparo-logo-width' => 55,
            '--mosparo-logo-height' => 10,
        ],
        'medium' => [
            '--mosparo-font-size' => 16,
            '--mosparo-line-height' => 22,
            '--mosparo-padding-top' => 20,
            '--mosparo-padding-left' => 24,
            '--mosparo-padding-right' => 20,
            '--mosparo-padding-bottom' => 20,
            '--mosparo-border-radius' => 11,
            '--mosparo-border-width' => 3,
            '--mosparo-container-min-width' => 320,
            '--mosparo-container-max-width' => 500,
            '--mosparo-circle-size' => 40,
            '--mosparo-circle-radius' => 20,
            '--mosparo-circle-border-width' => 3,
            '--mosparo-circle-offset' => -3,
            '--mosparo-circle-margin-right' => 20,
            '--mosparo-shadow-blur-radius' => 12,
            '--mosparo-shadow-spread-radius' => 3,
            '--mosparo-icon-border-offset' => -1,
            '--mosparo-icon-border-width' => 2,
            '--mosparo-checkmark-icon-height' => 10,
            '--mosparo-logo-left' => 10,
            '--mosparo-logo-bottom' => 5,
            '--mosparo-logo-width' => 70,
            '--mosparo-logo-height' => 15,
        ],
        'large' => [
            '--mosparo-font-size' => 24,
            '--mosparo-line-height' => 32,
            '--mosparo-padding-top' => 26,
            '--mosparo-padding-left' => 30,
            '--mosparo-padding-right' => 26,
            '--mosparo-padding-bottom' => 26,
            '--mosparo-border-radius' => 16,
            '--mosparo-border-width' => 4,
            '--mosparo-container-min-width' => 390,
            '--mosparo-container-max-width' => 570,
            '--mosparo-circle-size' => 44,
            '--mosparo-circle-radius' => 22,
            '--mosparo-circle-border-width' => 4,
            '--mosparo-circle-offset' => -4,
            '--mosparo-circle-margin-right' => 24,
            '--mosparo-shadow-blur-radius' => 16,
            '--mosparo-shadow-spread-radius' => 4,
            '--mosparo-icon-border-offset' => -2,
            '--mosparo-icon-border-width' => 4,
            '--mosparo-checkmark-icon-height' => 11,
            '--mosparo-logo-left' => 15,
            '--mosparo-logo-bottom' => 10,
            '--mosparo-logo-width' => 75,
            '--mosparo-logo-height' => 15,
        ],
    ];

    protected EntrypointLookupCollection $entrypointLookupCollection;

    protected Filesystem $filesystem;

    protected string $projectDirectory;

    public function __construct(EntrypointLookupCollection $entrypointLookupCollection, Filesystem $filesystem, string $projectDirectory)
    {
        $this->entrypointLookupCollection = $entrypointLookupCollection;
        $this->filesystem = $filesystem;
        $this->projectDirectory = $projectDirectory;
    }

    public function getBoxSizeVariables(): array
    {
        return self::$boxSizeVariables;
    }

    public function getBuildFilePath(string $relativePath): string
    {
        return $this->projectDirectory . '/public' . $relativePath;
    }

    public function getBaseCssFilePath(): ?string
    {
        $entrypoint = $this->entrypointLookupCollection->getEntrypointLookup();
        $cssFiles = $entrypoint->getCssFiles('mosparo-frontend');

        if (!$cssFiles) {
            return null;
        }

        $cssFile = current($cssFiles);
        return $this->getBuildFilePath($cssFile);
    }

    public function getCssFilePath(Project $project, string $designConfigHash = ''): string
    {
        if ($designConfigHash === '') {
            $designConfigHash = $project->getConfigValue('designConfigHash');
        }

        return $this->projectDirectory . '/public/resources/' . $project->getUuid() . '/' . $designConfigHash . '.css';
    }

    public function generateCssCache(Project $project)
    {
        $designConfigValues = $this->getDesignConfigValues($project);
        $designConfigHash = $this->generateDesignConfigHash($designConfigValues);

        // Prepare the css cache content
        $result = $this->createCssCache($project, $designConfigValues, $designConfigHash);

        // Update the design config hash
        if ($result) {
            $project->setConfigValue('designConfigHash', $designConfigHash);
        }
    }

    protected function getDesignConfigValues(Project $project): array
    {
        $configValues = [];

        foreach (self::$designConfigValueKeys as $designConfigValueKey) {
            $configValues[$designConfigValueKey] = $project->getConfigValue($designConfigValueKey);
        }

        return $configValues;
    }

    protected function generateDesignConfigHash(array $designConfigValues): string
    {
        $serializedValues = serialize($designConfigValues);
        return HashUtil::sha256Hash($serializedValues);
    }

    protected function createCssCache(Project $project, array $designConfigValues, string $designConfigHash): bool
    {
        $cssBaseFilePath = $this->getBaseCssFilePath();
        if (!$cssBaseFilePath || !$this->filesystem->exists($cssBaseFilePath)) {
            return false;
        }

        $content = $this->prepareCssCache($project, $designConfigValues, $cssBaseFilePath);

        // Path for CSS cache
        $cssFilePath = $this->getCssFilePath($project, $designConfigHash);
        $directoryPath = dirname($cssFilePath);

        try {
            // Create the project directory, if it does not exist
            if (!$this->filesystem->exists($directoryPath)) {
                $this->filesystem->mkdir($directoryPath, 0755);
            }

            // Write the CSS file
            $this->filesystem->dumpFile($cssFilePath, $content);

            // Cleanup the cache directory
            $directoryIterator = new DirectoryIterator($directoryPath);
            foreach ($directoryIterator as $file) {
                if ($file->isDot() || $file->getPathname() == $cssFilePath) {
                    continue;
                }

                $this->filesystem->remove($file->getPathname());
            }
        } catch (IOExceptionInterface $e) {
            return false;
        }

        return true;
    }

    protected function prepareCssCache(Project $project, array $designConfigValues, string $fullPath): string
    {
        $content = file_get_contents($fullPath);
        $defaultConfigValues = $project->getDefaultConfigValues();

        // Resolve the variables and replace them with the values
        $content = $this->resolveCssVariables($content, $designConfigValues, $defaultConfigValues);

        $content = preg_replace('%/\*(.[^*]*)\*/%i', '', $content);
        $content = str_replace(PHP_EOL, '', $content);

        // Include images and replace potential css variables
        return $this->includeImages($content, $designConfigValues, $defaultConfigValues);
    }

    protected function resolveCssVariables(string $content, array $designConfigValues, array $defaultConfigValues): string
    {
        foreach (self::$cssVariableNames as $cssVariableName => $configValue) {
            $key = $configValue['key'];
            $realValue = $designConfigValues[$key];
            $defaultValue = $defaultConfigValues[$key];

            $realValue = $this->translateValue($key, $realValue, $configValue['type']);
            $defaultValue = $this->translateValue($key, $defaultValue, $configValue['type']);

            $content = $this->replaceCssVariable($content, $cssVariableName, $defaultValue, $realValue);
        }

        $boxSize = $designConfigValues['boxSize'];
        $boxCssVariables = self::$boxSizeVariables[$boxSize];
        $defaultBoxCssVariables = self::$boxSizeVariables[$defaultConfigValues['boxSize']];
        foreach ($boxCssVariables as $cssVariableName => $value) {
            $realValue = $value . 'px';

            $content = $this->replaceCssVariable($content, $cssVariableName, $defaultBoxCssVariables[$cssVariableName] . 'px', $realValue);
        }

        return $content;
    }

    protected function replaceCssVariable($content, $cssVariableName, $defaultValue, $realValue)
    {
        $fullVar = 'var(' . $cssVariableName . ', ' . $defaultValue . ')';

        return str_replace([
                $fullVar,
                str_replace([', ', '0.'], [',', '.'], $fullVar),
            ],
            $realValue,
            $content
        );
    }

    protected function translateValue($key, $value, $type)
    {
        if ($type == 'color' && $value == '') {
            $value = 'transparent';
        } else if ($type == 'number') {
            $value = $value . 'px';
        } else if ($type == 'bool') {
            if ($key == 'showMosparoLogo') {
                if ($value) {
                    $value = 'block';
                } else {
                    $value = 'none';
                }
            } else if ($key == 'showPingAnimation') {
                if ($value) {
                    $value = 'mosparo__ping-animation';
                } else {
                    $value = 'none';
                }
            }
        }

        return $value;
    }

    protected function includeImages(string $content, array $designConfigValues, array $defaultConfigValues): string
    {
        $mimeTypes = new MimeTypes();

        preg_match_all('/(url\((.[^)]*)\))/i', $content, $results, PREG_SET_ORDER);
        foreach ($results as $result) {
            if (strpos($result[2], 'mosparo_text_logo') !== false && isset($designConfigValues['showMosparoLogo']) && !$designConfigValues['showMosparoLogo']) {
                $content = str_replace($result[1], '', $content);
                continue;
            }

            $filePath = $this->getBuildFilePath($result[2]);
            if (!file_exists($filePath)) {
                continue;
            }

            $fileContent = file_get_contents($filePath);

            $mimeType = $mimeTypes->guessMimeType($filePath);
            if ($mimeType == null) {
                continue;
            }

            if ($mimeType == 'image/svg') {
                $encodedFileContent = ';utf8,' . $this->resolveCssVariables($fileContent, $designConfigValues, $defaultConfigValues);
                $mimeType = 'image/svg+xml';
            } else {
                $encodedFileContent = ';base64,' . base64_encode($fileContent);
            }

            $content = str_replace($result[2], '\'data:' . $mimeType . $encodedFileContent . '\'', $content);
        }

        return $content;
    }
}