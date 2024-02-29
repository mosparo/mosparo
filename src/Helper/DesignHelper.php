<?php

namespace Mosparo\Helper;

use DirectoryIterator;
use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\Project;
use Mosparo\Util\HashUtil;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupCollection;

class DesignHelper
{
    protected static array $designConfigValueKeys = [
        'displayContent',
        'positionContainer',
        'boxSize',
        'boxRadius',
        'boxBorderWidth',
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

        'fullPageOverlay',
        'colorLoaderBackground',
        'colorLoaderText',
        'colorLoaderCircle',
    ];

    protected static array $cssVariableNames = [
        '--mosparo-content-display' => ['key' => 'displayContent', 'type' => 'string'],
        '--mosparo-container-position' => ['key' => 'positionContainer', 'type' => 'string'],
        '--mosparo-border-color' => ['key' => 'colorBorder', 'type' => 'color'],
        '--mosparo-border-radius' => ['key' => 'boxRadius', 'type' => 'number'],
        '--mosparo-border-width' => ['key' => 'boxBorderWidth', 'type' => 'number'],
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

        '--mosparo-loader-position' => ['key' => 'fullPageOverlay', 'type' => 'bool'],
        '--mosparo-loader-background-color' => ['key' => 'colorLoaderBackground', 'type' => 'color'],
        '--mosparo-loader-text-color' => ['key' => 'colorLoaderText', 'type' => 'color'],
        '--mosparo-loader-circle-color' => ['key' => 'colorLoaderCircle', 'type' => 'color'],
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
            '--mosparo-container-min-width' => 300,
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
            '--mosparo-container-min-width' => 300,
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
        'invisible' => [
            '--mosparo-font-size' => 16,
            '--mosparo-line-height' => 22,
            '--mosparo-padding-top' => 0,
            '--mosparo-padding-left' => 0,
            '--mosparo-padding-right' => 0,
            '--mosparo-padding-bottom' => 0,
            '--mosparo-border-radius' => 0,
            '--mosparo-border-width' => 0,
            '--mosparo-container-min-width' => 'unset',
            '--mosparo-container-max-width' => 'unset',
            '--mosparo-circle-size' => 0,
            '--mosparo-circle-radius' => 0,
            '--mosparo-circle-border-width' => 0,
            '--mosparo-circle-offset' => 0,
            '--mosparo-circle-margin-right' => 0,
            '--mosparo-shadow-blur-radius' => 0,
            '--mosparo-shadow-spread-radius' => 0,
            '--mosparo-icon-border-offset' => 0,
            '--mosparo-icon-border-width' => 0,
            '--mosparo-checkmark-icon-height' => 0,
            '--mosparo-logo-left' => 0,
            '--mosparo-logo-bottom' => 0,
            '--mosparo-logo-width' => 0,
            '--mosparo-logo-height' => 0,
        ],
    ];

    protected static $maxRadiusForLogo = ['small' => 15, 'medium' => 20, 'large' => 35];

    protected EntityManagerInterface $entityManager;

    protected ProjectHelper $projectHelper;

    protected EntrypointLookupCollection $entrypointLookupCollection;

    protected UrlGeneratorInterface $router;

    protected Filesystem $filesystem;

    protected string $projectDirectory;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProjectHelper $projectHelper,
        EntrypointLookupCollection $entrypointLookupCollection,
        UrlGeneratorInterface $router,
        Filesystem $filesystem,
        string $projectDirectory
    ) {
        $this->entityManager = $entityManager;
        $this->projectHelper = $projectHelper;
        $this->entrypointLookupCollection = $entrypointLookupCollection;
        $this->router = $router;
        $this->filesystem = $filesystem;
        $this->projectDirectory = $projectDirectory;
    }

    public function getTextLogoContent()
    {
        $path = $this->getBuildFilePath('/build/images/mosparo_text_logo.svg');
        if (!$this->filesystem->exists($path)) {
            return '';
        }

        return file_get_contents($path);
    }

    public function refreshFrontendResourcesForAllProjects()
    {
        // Get the originally active project to set it again after the refresh.
        $activeProject = $this->projectHelper->getActiveProject();

        $projectRepository = $this->entityManager->getRepository(Project::class);
        $projects = $projectRepository->findAll();

        foreach ($projects as $project) {
            $this->projectHelper->setActiveProject($project);
            $this->generateCssCache($project);
        }

        // Store the new design hashes. Otherwise, the resources will not be found correctly.
        $this->entityManager->flush();

        // Set the originally active project again
        $this->projectHelper->setActiveProject($activeProject);
    }

    public function getBoxSizeVariables(): array
    {
        return self::$boxSizeVariables;
    }

    public function getMaxRadiusForLogo(): array
    {
        return self::$maxRadiusForLogo;
    }

    public function getBuildFilePath(string $relativePath): string
    {
        return $this->projectDirectory . '/public' . $relativePath;
    }

    public function getBaseCssFileName(): ?string
    {
        $entrypoint = $this->entrypointLookupCollection->getEntrypointLookup();
        $cssFiles = $entrypoint->getCssFiles('mosparo-frontend');

        if (!$cssFiles) {
            return null;
        }

        $entrypoint->reset();

        return current($cssFiles);
    }

    public function getBaseCssFilePath(): ?string
    {
        return $this->getBuildFilePath($this->getBaseCssFileName());
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

        // Adjust the visibility of the logo
        $designConfigValues = $this->adjustLogoVisibility($designConfigValues);

        // Prepare the css cache content
        $result = $this->createCssCache($project, $designConfigValues, $designConfigHash);

        // Update the design config hash
        if ($result) {
            $project->setConfigValue('designConfigHash', $designConfigHash);

            // Update the mapping file
            $projectUri = $this->router->generate('resources_project_css', ['projectUuid' => $project->getUuid()]);
            $cssUri = $this->router->generate('resources_project_hash_css', ['projectUuid' => $project->getUuid(), 'styleHash' => $designConfigHash]);
            $this->addMapping($projectUri, $cssUri);
        }
    }

    public function clearCssCache(Project $project)
    {
        $designConfigHash = $project->getConfigValue('designConfigHash');
        if ($designConfigHash === null) {
            return;
        }

        // Delete the project directory in the cache directory
        $cssFilePath = $this->getCssFilePath($project, $designConfigHash);
        $directoryPath = dirname($cssFilePath);
        $this->filesystem->remove($directoryPath);

        // Remove the url from the mapping file
        $projectUri = $this->router->generate('resources_project_css', ['projectUuid' => $project->getUuid()]);
        $this->removeMapping($projectUri);
    }

    public function prepareCssVariables(Project $project): array
    {
        $designConfigValues = $this->getDesignConfigValues($project);
        $variables = [];
        foreach (self::$cssVariableNames as $cssVariableName => $configValue) {
            $key = $configValue['key'];
            $realValue = $designConfigValues[$key];

            $realValue = $this->translateValue($key, $realValue, $configValue['type']);

            $variables[$cssVariableName] = $realValue;
        }

        return $variables;
    }

    protected function adjustLogoVisibility($configValues): array
    {
        $size = $configValues['boxSize'] ?? 'medium';
        $radius = $configValues['boxRadius'] ?? 0;
        $maxRadius = self::$maxRadiusForLogo[$size] ?? 0;

        if ($radius > $maxRadius) {
            $configValues['showMosparoLogo'] = false;
        }

        return $configValues;
    }

    protected function loadMappings(): array
    {
        $path = $this->getBuildFilePath('/resources/mappings.php');
        if ($this->filesystem->exists($path)) {
            return include($path);
        }

        return [];
    }

    protected function addMapping(string $projectUri, string $cssUri)
    {
        $mappings = array_merge($this->loadMappings(), [$projectUri => $cssUri]);

        $this->storeMappings($mappings);
    }

    protected function removeMapping(string $projectUri)
    {
        $mappings = $this->loadMappings();
        if (!isset($mappings[$projectUri])) {
            return;
        }

        unset($mappings[$projectUri]);
        $this->storeMappings($mappings);
    }

    protected function storeMappings(array $mappings)
    {
        $path = $this->getBuildFilePath('/resources/mappings.php');
        $content = '<?php' . PHP_EOL . PHP_EOL . 'return ' . var_export($mappings, true) . ';' . PHP_EOL;

        $this->filesystem->dumpFile($path, $content);

        // Invalidate the cache for the environment file, if opcache is enabled
        if (function_exists('opcache_is_script_cached') && opcache_is_script_cached($path)) {
            opcache_invalidate($path, true);
        }
    }

    protected function getDesignConfigValues(Project $project): array
    {
        $configValues = [];

        if (in_array($project->getDesignMode(), ['simple', 'invisible-simple'])) {
            $defaultConfigValues = $project->getDefaultConfigValues();
            foreach (self::$designConfigValueKeys as $designConfigValueKey) {
                $configValues[$designConfigValueKey] = $defaultConfigValues[$designConfigValueKey] ?? '';
            }

            if ($project->getDesignMode() === 'simple') {
                $configValues = $this->getSimpleModeValues($project, $configValues);
            } else if ($project->getDesignMode() === 'invisible-simple') {
                $configValues = $this->getInvisibleSimpleModeValues($project, $configValues);
            }
        } else {
            foreach (self::$designConfigValueKeys as $designConfigValueKey) {
                $configValues[$designConfigValueKey] = $project->getConfigValue($designConfigValueKey);
            }
        }

        return $configValues;
    }

    protected function getSimpleModeValues(Project $project, $configValues)
    {
        $configValues['boxSize'] = $project->getConfigValue('boxSize');

        $colorWebsiteBackground = $project->getConfigValue('colorWebsiteBackground');
        $configValues['colorBackground'] = $colorWebsiteBackground;
        $configValues['colorSuccessBackground'] = $colorWebsiteBackground;
        $configValues['colorFailureBackground'] = $colorWebsiteBackground;

        $colorWebsiteForeground = $project->getConfigValue('colorWebsiteForeground');
        $configValues['colorText'] = $colorWebsiteForeground;

        $colorWebsiteAccent = $project->getConfigValue('colorWebsiteAccent');
        $configValues['colorBorder'] = $colorWebsiteAccent;
        $configValues['colorCheckbox'] = $colorWebsiteAccent;
        $configValues['colorLoadingCheckboxAnimatedCircle'] = $colorWebsiteAccent;
        $configValues['colorFocusCheckbox'] = $colorWebsiteAccent;

        $colorHover = $project->getConfigValue('colorHover');
        $configValues['colorFocusCheckboxShadow'] = $colorHover;

        $colorSuccess = $project->getConfigValue('colorSuccess');
        $configValues['colorSuccessBorder'] = $colorSuccess;
        $configValues['colorSuccessCheckbox'] = $colorSuccess;
        $configValues['colorSuccessText'] = $colorSuccess;

        $colorFailure = $project->getConfigValue('colorFailure');
        $configValues['colorFailureBorder'] = $colorFailure;
        $configValues['colorFailureCheckbox'] = $colorFailure;
        $configValues['colorFailureText'] = $colorFailure;
        $configValues['colorFailureTextError'] = $colorFailure;

        $transparent = 'transparent';
        $configValues['colorShadow'] = $transparent;
        $configValues['colorSuccessShadow'] = $transparent;
        $configValues['colorFailureShadow'] = $transparent;

        return $configValues;
    }

    protected function getInvisibleSimpleModeValues(Project $project, $configValues)
    {
        $transparent = 'transparent';
        $configValues['colorBackground'] = $transparent;
        $configValues['colorSuccessBackground'] = $transparent;
        $configValues['colorFailureBackground'] = $transparent;
        $configValues['colorText'] = $transparent;
        $configValues['colorBorder'] = $transparent;
        $configValues['colorCheckbox'] = $transparent;
        $configValues['colorLoadingCheckboxAnimatedCircle'] = $transparent;
        $configValues['colorFocusCheckbox'] = $transparent;
        $configValues['colorFocusCheckboxShadow'] = $transparent;
        $configValues['colorSuccessBorder'] = $transparent;
        $configValues['colorSuccessCheckbox'] = $transparent;
        $configValues['colorSuccessText'] = $transparent;
        $configValues['colorFailureBorder'] = $transparent;
        $configValues['colorFailureCheckbox'] = $transparent;
        $configValues['colorFailureText'] = $transparent;
        $configValues['colorShadow'] = $transparent;
        $configValues['colorSuccessShadow'] = $transparent;
        $configValues['colorFailureShadow'] = $transparent;

        $configValues['boxSize'] = 'invisible';
        $configValues['displayContent'] = 'none !important';
        $configValues['positionContainer'] = 'static';
        $configValues['boxRadius'] = 0;
        $configValues['boxBorderWidth'] = 0;

        $configValues['fullPageOverlay'] = $project->getConfigValue('fullPageOverlay');
        $configValues['colorLoaderBackground'] = $project->getConfigValue('colorLoaderBackground');
        $configValues['colorLoaderText'] = $project->getConfigValue('colorLoaderText');
        $configValues['colorLoaderCircle'] = $project->getConfigValue('colorLoaderCircle');
        $configValues['colorFailureTextError'] = $project->getConfigValue('colorFailureTextError');
        $configValues['showMosparoLogo'] = $project->getConfigValue('showMosparoLogo');

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

        // Replace the .mosparo__container selector with .mosparo__{projectUuid} to allow multiple different
        // mosparo designs on the same website.
        $content = str_replace('.mosparo__container', '.mosparo__' . $project->getUuid(), $content);

        // Remove comments and new lines
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
            $realValue = $value;
            if (is_numeric($value)) {
                $realValue = $value . 'px';
            }

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
            } else if ($key == 'fullPageOverlay') {
                if ($value) {
                    $value = 'fixed';
                } else {
                    $value = 'absolute';
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

            $content = str_replace($result[2], '/resources/logo.svg', $content);
        }

        return $content;
    }
}