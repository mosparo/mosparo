<?php

namespace Mosparo\Helper;

use Mosparo\Exception;
use Mosparo\Kernel;
use Mosparo\Message\UpdateMessage;
use Mosparo\Specifications\Specifications;
use Opis\JsonSchema\Validator;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\NativeHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;

class UpdateHelper
{
    const MOSPARO_UPDATE_URL = 'https://updates.mosparo.io/v/{majorVersion}.json';
    const FILE_COPY = 'copy';
    const FILE_DELETE = 'delete';

    /**
     * @var \Mosparo\Helper\ConfigHelper
     */
    protected ConfigHelper $configHelper;

    protected ConnectionHelper $connectionHelper;

    /**
     * @var \Symfony\Contracts\HttpClient\HttpClientInterface
     */
    protected HttpClientInterface $client;

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected Filesystem $fileSystem;

    /**
     * @var \Symfony\Contracts\Cache\CacheInterface
     */
    protected CacheInterface $cache;

    /**
     * @var string
     */
    protected string $projectDirectory;

    /**
     * @var string
     */
    protected string $cacheDirectory;

    /**
     * @var string
     */
    protected string $env;

    /**
     * @var callable
     */
    protected $outputHandler = null;

    /**
     * @var array
     */
    protected array $newVersionData = [];

    /**
     * @var bool
     */
    protected bool $updateAvailable = false;

    /**
     * @var array
     */
    protected array $upgradeData = [];

    /**
     * @var bool
     */
    protected bool $upgradeAvailable = false;

    /**
     * @var array
     */
    protected array $writableDirectories = [];

    /**
     * Constructs the object
     *
     * @param \Mosparo\Helper\ConfigHelper $configHelper
     * @param \Symfony\Contracts\HttpClient\HttpClientInterface $client
     * @param \Symfony\Component\Filesystem\Filesystem $fileSystem
     * @param \Symfony\Contracts\Cache\CacheInterface $cache
     * @param string $projectDirectory
     * @param string $cacheDirectory
     * @param string $env
     */
    public function __construct(ConfigHelper $configHelper, ConnectionHelper $connectionHelper, HttpClientInterface $client, Filesystem $fileSystem, CacheInterface $cache, string $projectDirectory, string $cacheDirectory, string $env)
    {
        $this->configHelper = $configHelper;
        $this->connectionHelper = $connectionHelper;
        $this->client = $client;
        $this->fileSystem = $fileSystem;
        $this->cache = $cache;
        $this->projectDirectory = $projectDirectory;
        $this->cacheDirectory = $cacheDirectory;
        $this->env = $env;
    }

    /**
     * Outputs the given message
     *
     * @param \Mosparo\Message\UpdateMessage $data
     */
    public function output(UpdateMessage $data)
    {
        if ($this->outputHandler === null || !is_callable($this->outputHandler)) {
            return;
        }

        call_user_func($this->outputHandler, $data);
    }

    /**
     * Sets the output handler
     *
     * @param callable $outputHandler
     */
    public function setOutputHandler($outputHandler)
    {
        $this->outputHandler = $outputHandler;
    }

    /**
     * Returns true if we have cached update data
     *
     * @return bool
     */
    public function hasCachedData(): bool
    {
        return (bool) ($this->getCachedUpdateData(false)['checkedAt'] ?? false);
    }

    /**
     * Returns true if there is an update available
     *
     * @param bool $checkForUpdates
     * @return bool
     */
    public function isUpdateAvailable(bool $checkForUpdates = false): bool
    {
        return $this->getCachedUpdateData($checkForUpdates)['isUpdateAvailable'] ?? false;
    }

    /**
     * Returns the available update data
     *
     * @param bool $checkForUpdates
     * @return array
     */
    public function getAvailableUpdateData(bool $checkForUpdates = false): array
    {
        return $this->getCachedUpdateData($checkForUpdates)['availableUpdate'] ?? [];
    }

    /**
     * Returns true if there is an upgrade available
     *
     * @return bool
     */
    public function isUpgradeAvailable(): bool
    {
        return ($this->upgradeAvailable); // @TODO
    }

    /**
     * Returns the data for the available upgrade
     *
     * @return array
     */
    public function getAvailableUpgradeData(): array
    {
        return $this->upgradeData; // @TODO
    }

    /**
     * Returns a DateTime object, at which the update check was done, or returns null
     * if no cached data is available.
     *
     * @return \DateTime|null
     */
    public function getCheckedAt(): ?\DateTime
    {
        return $this->getCachedUpdateData(false)['checkedAt'] ?? null;
    }


    /**
     * Defines and creates the directory for the update log file
     *
     * @return string
     */
    public function getUpdateLogFileDirectory(): string
    {
        $directory = $this->projectDirectory . '/public/update-log';
        if (!$this->fileSystem->exists($directory)) {
            $this->fileSystem->mkdir($directory);
        }

        return $directory;
    }

    /**
     * Returns an array with the path and the absolute URI to the
     * temporary log file.
     *
     * @return array
     */
    public function defineTemporaryLogFile(): array
    {
        $directory = $this->getUpdateLogFileDirectory();
        $fileName = '/update-log-' . uniqid() . '.html';
        $filePath = $directory . $fileName;

        $fileUrl = '/update-log' . $fileName;

        return [$filePath, $fileUrl];
    }

    /**
     * Returns the data for the available update
     *
     * @param bool $checkForUpdates
     * @return array
     */
    public function getCachedUpdateData(bool $checkForUpdates = false): array
    {
        $cacheKey = 'availableUpdateData';
        if ($checkForUpdates) {
            $this->cache->delete($cacheKey);
        }

        $cachedData = $this->cache->get($cacheKey, function (CacheItemInterface $item) use ($checkForUpdates) {
            if ($checkForUpdates) {
                $item->expiresAfter(86400);

                $this->checkForUpdates();

                $data = [
                    'isUpdateAvailable' => !empty($this->newVersionData),
                    'availableUpdate' => $this->newVersionData,
                    'checkedAt' => new \DateTime(),
                ];

                $item->set($data);

                return $data;
            }

            return $item->get();
        });

        if ($cachedData === null) {
            return [];
        }

        return $cachedData;
    }

    /**
     * Downloads the latest version data from the mosparo update site and determines, if there is an update
     * available for this mosparo installation
     *
     * @throws \Mosparo\Exception Signature validation failed for "{URL}".
     * @throws \Mosparo\Exception The update data is not valid against the schema.
     */
    public function checkForUpdates()
    {
        $channel = $this->configHelper->getEnvironmentConfigValue('updateChannel', 'stable');
        $url = str_replace('{majorVersion}', Kernel::MAJOR_VERSION, self::MOSPARO_UPDATE_URL);

        // Get the version data and the signature
        $versionData = $this->loadRemoteData($url);
        $signature = $this->loadRemoteData($url . '.signature');

        if (!$this->validateSignature($versionData, $signature)) {
            throw new Exception(sprintf('Signature validation failed for "%s".', $url));
        }

        if (!$this->validateUpdateData($versionData)) {
            throw new Exception('The update data is not valid against the schema.');
        }

        // Parse the data and process it
        $updateData = json_decode($versionData, true);

        $this->processVersionData($updateData, $channel);
    }

    /**
     * Validates the given version data against the update JSON schema
     *
     * @param string $versionData
     * @return bool
     */
    protected function validateUpdateData(string $versionData): bool
    {
        $jsonData = json_decode($versionData);

        $validator = new Validator();
        $validator->resolver()->registerPrefix('http://schema.mosparo.io/', Specifications::getJsonSchemaPath(''));

        $result = $validator->validate($jsonData, 'http://schema.mosparo.io/version.json');

        return $result->isValid();
    }

    /**
     * Updates the mosparo installation. Returns true if everything worked correctly or false if an
     * error occurred.
     *
     * @param array $versionData
     * @return bool
     */
    public function updateMosparo(array $versionData): bool
    {
        try {
            $destinationPath = $this->projectDirectory;

            // This is a special hack to prevent updating the development instance
            if ($this->env === 'dev') {
                $destinationPath = sys_get_temp_dir() . '/mosparo-test';

                if (!$this->fileSystem->exists($destinationPath)) {
                    $this->fileSystem->mkdir($destinationPath);
                }
            }

            // Download the file
            $this->output(new UpdateMessage('download_update', UpdateMessage::STATUS_IN_PROGRESS, 'Download update'));
            $filePath = $this->downloadUpdate($versionData['downloadUrl']);

            // Validate the downloaded file with the signature from the version data
            $this->output(new UpdateMessage('validate_download', UpdateMessage::STATUS_IN_PROGRESS, 'Validate downloaded file'));
            $isValid = $this->validateFileSignature($filePath, $versionData['downloadSignature']);

            if (!$isValid) {
                throw new Exception('Signature not valid.');
            }

            // Extract the update
            $this->output(new UpdateMessage('extract_update', UpdateMessage::STATUS_IN_PROGRESS, 'Extract update'));
            $sourcePath = $this->extractUpdate($filePath);

            // Download the hash file
            $this->output(new UpdateMessage('download_hash_file', UpdateMessage::STATUS_IN_PROGRESS, 'Download hash file'));
            $hashes = $this->downloadHashFile($versionData['hashesUrl'], $versionData['hashesSignature']);

            // Define the needed changes
            $this->output(new UpdateMessage('calculate_changes', UpdateMessage::STATUS_IN_PROGRESS, 'Calculate changes'));
            $changes = $this->calculateChanges($sourcePath, $destinationPath, $hashes);

            // Test if it is possible to apply the changes (is writeable)
            $this->output(new UpdateMessage('simulate_changes', UpdateMessage::STATUS_IN_PROGRESS, 'Simulate update'));
            $this->simulateChanges($changes);

            // Execute the changes
            $this->output(new UpdateMessage('execute_changes', UpdateMessage::STATUS_IN_PROGRESS, 'Update mosparo'));
            $this->executeChanges($changes);

            // Validate the files
            $this->output(new UpdateMessage('validate_files', UpdateMessage::STATUS_IN_PROGRESS, 'Validate the updated files'));
            $this->validateUpdatedFiles($destinationPath, $changes, $hashes);

            // Cleanup
            $this->output(new UpdateMessage('cleanup', UpdateMessage::STATUS_IN_PROGRESS, 'Cleanup the files'));
            $this->cleanup($filePath, $sourcePath);
        } catch (\Exception $e) {
            $this->output(new UpdateMessage('error', UpdateMessage::STATUS_ERROR, $e->getMessage()));
            return false;
        }

        return true;
    }

    /**
     * Downloads the data from the given url and returns the content.
     *
     * @param string $url
     * @param array $args
     * @return string
     *
     * @throws \Mosparo\Exception Downloading files from the internet is impossible because requirements need to be met. Please check the system page or the mosparo documentation.
     * @throws \Mosparo\Exception Cannot load the remote data for the given url "{URL}".
     */
    protected function loadRemoteData(string $url, array $args = []): string
    {
        if (!$this->connectionHelper->isDownloadPossible()) {
            throw new Exception('Downloading files from the internet is impossible because requirements need to be met. Please check the system page or the mosparo documentation.');
        }

        $client = $this->client;
        if ($this->connectionHelper->useNativeConnection()) {
            $client = new NativeHttpClient();
        }

        try {
            $response = $client->request('GET', $url, $args);
        } catch (\Exception $e) {
            throw new Exception(sprintf('Cannot load the remote data for the given url "%s".', $url), 0, $e);
        }

        if ($response->getStatusCode() !== 200) {
            throw new Exception(sprintf('Cannot load the remote data for the given url "%s".', $url));
        }

        return $response->getContent();
    }

    /**
     * Downloads the data from the url by using a stream and saves the data to the destination file path.
     * Useful when downloading big files.
     *
     * @param string $url
     * @param string $destinationFilePath
     *
     * @throws \Mosparo\Exception Downloading files from the internet is impossible because requirements need to be met. Please check the system page or the mosparo documentation.
     * @throws \Mosparo\Exception Cannot load the remote data for the given url "{URL}".
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    protected function streamRemoteData(string $url, string $destinationFilePath)
    {
        if (!$this->connectionHelper->isDownloadPossible()) {
            throw new Exception('Downloading files from the internet is impossible because requirements need to be met. Please check the system page or the mosparo documentation.');
        }

        $client = $this->client;
        if ($this->connectionHelper->useNativeConnection()) {
            $client = new NativeHttpClient();
        }

        $response = $client->request('GET', $url);

        if ($response->getStatusCode() !== 200) {
            throw new Exception(sprintf('Cannot load the remote data for the given url "%s".', $url));
        }

        $fileHandler = fopen($destinationFilePath, 'w');
        foreach ($this->client->stream($response) as $chunk) {
            fwrite($fileHandler, $chunk->getContent());
        }
    }

    /**
     * Creates a SHA512 hash for the given string of data.
     *
     * @param string $data
     * @return string
     */
    protected function createHash(string $data): string
    {
        return hash('sha512', $data);
    }

    /**
     * Creates the hash of the given file. Uses SHA512 by default.
     *
     * @param string $filePath
     * @param string $algo
     * @return string
     */
    protected function createFileHash(string $filePath, string $algo = 'sha512'): string
    {
        return hash_file($algo, $filePath);
    }

    /**
     * Decrypts the given signature by using the public mosparo updates key.
     *
     * @param string $signature
     * @return string
     */
    protected function decryptSignature(string $signature): string
    {
        $signature = base64_decode($signature);

        openssl_public_decrypt($signature, $decryptedHash, $this->loadPublicKey());

        return trim($decryptedHash);
    }

    /**
     * Loads the public mosparo updates key.
     *
     * @return string
     */
    protected function loadPublicKey(): string
    {
        $path = $this->projectDirectory . '/config/keys/updates.mosparo.io_public_key.pem';

        return file_get_contents($path);
    }

    /**
     * Validates the given data with the given signature
     *
     * @param string $data
     * @param string $signature
     * @return bool
     */
    protected function validateSignature(string $data, string $signature): bool
    {
        $dataHash = $this->createHash($data);
        $originHash = $this->decryptSignature($signature);

        if ($dataHash === $originHash) {
            return true;
        }

        return false;
    }

    /**
     * Validates the signature of the specified file with the given signature.
     *
     * @param string $filePath
     * @param string $signature
     * @return bool
     */
    protected function validateFileSignature(string $filePath, string $signature): bool
    {
        $dataHash = $this->createFileHash($filePath);
        $originHash = $this->decryptSignature($signature);

        if ($dataHash === $originHash) {
            return true;
        }

        return false;
    }

    /**
     * Processes the version data and tries to determine the next available version.
     *
     * @param array $versionData
     * @param string $channel
     */
    protected function processVersionData(array $versionData, string $channel): void
    {
        $channelData = $versionData['channels'][$channel] ?? null;

        if (!$channelData || !($channelData['latestVersion'] ?? null)) {
            throw new Exception('The update information for the selected channel are not available.');
        }

        if (version_compare(Kernel::VERSION, $channelData['latestVersion'], '<')) {
            $this->newVersionData = $this->loadVersionChannelVersions($channelData['versionsUrl']);
            $this->updateAvailable = (!empty($this->newVersionData));
        }

        $recommendUpgrade = $channelData['recommendNextMajorVersion'] ?? false;
        if ($recommendUpgrade) {
            try {
                $this->loadUpgradeInformation($channelData, $channel);
            } catch (Exception $e) {
                // We hide the exception since the upgrade is optional.
                // If the schema has changed, the user may have to upgrade
                // to the latest mosparo version before the upgrade is possible.
            }
        }
    }

    protected function loadVersionChannelVersions($url): array
    {
        $versionDataRaw = $this->loadRemoteData($url);
        $signature = $this->loadRemoteData($url . '.signature');

        if (!$this->validateSignature($versionDataRaw, $signature)) {
            throw new Exception(sprintf('Signature validation failed for "%s".', $url));
        }

        if (!$this->validateVersionChannelVersions($versionDataRaw)) {
            throw new Exception('The update data is not valid against the schema.');
        }

        // Parse the data and process it
        $versionData = json_decode($versionDataRaw, true);
        $newVersionData = [];
        foreach ($versionData['versions'] as $version) {
            if (
                version_compare(Kernel::VERSION, $version['number'], '<')
                && (empty($newVersionData) || version_compare($newVersionData['number'], $version['number'], '<'))
            ) {
                $newVersionData = $version;
            }
        }

        return $newVersionData;
    }

    protected function validateVersionChannelVersions(string $versionData): bool
    {
        $jsonData = json_decode($versionData);

        $validator = new Validator();
        $validator->resolver()->registerPrefix('http://schema.mosparo.io/', Specifications::getJsonSchemaPath(''));

        $result = $validator->validate($jsonData, 'http://schema.mosparo.io/version-channel-versions.json');

        return $result->isValid();
    }

    protected function loadUpgradeInformation(array $channelData, $channel)
    {
        // Get the version data and the signature
        $versionData = $this->loadRemoteData($channelData['nextMajorVersionUrl']);
        $signature = $this->loadRemoteData($channelData['nextMajorVersionUrl'] . '.signature');

        if (!$this->validateSignature($versionData, $signature)) {
            throw new Exception(sprintf('Signature validation failed for "%s".', $channelData['nextMajorVersionUrl']));
        }

        if (!$this->validateUpdateData($versionData)) {
            throw new Exception('The update data is not valid against the schema.');
        }

        // Parse the data and process it
        $updateData = json_decode($versionData, true);

        $channelData = $updateData['channels'][$channel] ?? null;

        if (!$channelData || !($channelData['latestVersion'] ?? null)) {
            throw new Exception('The update information for the selected channel are not available.');
        }

        if (version_compare(Kernel::VERSION, $channelData['latestVersion'], '<')) {
            $this->upgradeData['majorVersionData'] = $updateData;
            $this->upgradeData['versionData'] = $this->loadVersionChannelVersions($channelData['versionsUrl']);
            $this->upgradeAvailable = (!empty($this->upgradeData));
        }
    }

    /**
     * Downloads the update from the given url. Returns the file path where the downloaded file was stored.
     *
     * @param string $url
     * @return string
     *
     * @throws \Mosparo\Exception Cannot load the remote data for the given url "{URL}".
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    protected function downloadUpdate(string $url): string
    {
        $updateFileData = $this->projectDirectory . '/var/updates/update-' . uniqid() . '.zip';
        if (!$this->fileSystem->exists(dirname($updateFileData))) {
            $this->fileSystem->mkdir(dirname($updateFileData));
        }

        $this->streamRemoteData($url, $updateFileData);

        return $updateFileData;
    }

    /**
     * Extracts the downloaded update file. Returns the path where the extracted data is stored.
     *
     * @param string $filePath
     * @return string
     * @throws \Mosparo\Exception Could not open update file. Error: {ERROR}
     */
    protected function extractUpdate(string $filePath): string
    {
        $zip = new \ZipArchive();

        if (!($res = $zip->open($filePath))) {
            throw new Exception(sprintf('Could not open update file. Error: %s', $res));
        }

        $updateDir = $this->projectDirectory . '/var/updates/update-extracted-' . uniqid();
        if (!$this->fileSystem->exists($updateDir)) {
            $this->fileSystem->mkdir($updateDir);
        }

        $zip->extractTo($updateDir);
        $zip->close();

        return $updateDir;
    }

    /**
     * Downloads the hashes file for the given update url. Returns the array with all hashes from the hash file.
     *
     * @param string $hashesUrl
     * @param string $signature
     * @return array
     *
     * @throws \Mosparo\Exception Cannot download hash file.
     * @throws \Mosparo\Exception Signature validation failed for "{URL}".
     */
    protected function downloadHashFile(string $hashesUrl, string $hashesSignature): array
    {
        try {
            $content = $this->loadRemoteData($hashesUrl);
        } catch (\Exception $e) {
            throw new Exception('Cannot download hash file.', 0, $e);
        }

        if (!$this->validateSignature($content, $hashesSignature)) {
            throw new Exception(sprintf('Signature validation failed for "%s".', $hashesUrl));
        }

        $hashes = [];
        $lines = explode(PHP_EOL, $content);
        foreach ($lines as $line) {
            $data = explode('  ', $line);
            if (count($data) === 2) {
                $path = ltrim($data[1], '.');
                $hashes[$path] = $data[0];
            }
        }

        return $hashes;
    }

    /**
     * Calculates the changes which are needed to update the mosparo installation. If a file exists in both directories,
     * the hashes of the two files will be compared and if they are different, the file will be updated. The process
     * also verifies the hashes of all downloaded files to make sure that the extracted files were correct.
     *
     * @param string $sourcePath
     * @param string $destinationPath
     * @param array $hashes
     * @return array
     *
     * @throws \Mosparo\Exception Cannot generate the list with files.
     */
    protected function calculateChanges(string $sourcePath, string $destinationPath, array $hashes): array
    {
        $changes = [];

        try {
            $sourceFiles = $this->getFileList($sourcePath, $hashes);
            $destinationFiles = $this->getFileList($destinationPath);
        } catch (Exception $e) {
            throw new Exception('Cannot generate the list with files.', 0, $e);
        }


        $files = array_unique(array_merge($sourceFiles, $destinationFiles));

        // Calculate the differences
        foreach ($files as $file) {
            $sourceExists = $this->fileSystem->exists($sourcePath . $file);
            $destinationExists = $this->fileSystem->exists($destinationPath . $file);

            if ($sourceExists && !$destinationExists) {
                // Only source file exists, copy file to destination
                $changes[] = [
                    'mode' => self::FILE_COPY,
                    'source' => $sourcePath . $file,
                    'destination' => $destinationPath . $file,
                ];
            } else if ($sourceExists && $destinationExists) {
                // Create the hashes of the two files and if they are different, add a change to copy the file
                $hashSource = $this->createFileHash($sourcePath . $file, 'sha256');
                $hashDestination = $this->createFileHash($destinationPath . $file, 'sha256');

                if ($hashSource !== $hashDestination) {
                    $changes[] = [
                        'mode' => self::FILE_COPY,
                        'source' => $sourcePath . $file,
                        'destination' => $destinationPath . $file,
                    ];
                }
            } else if (!$sourceExists && $destinationExists) {
                if ($this->ignoreInDestinationExistingFiles($file)) {
                    continue;
                }

                // Only destination file exists, delete destination file
                $changes[] = [
                    'mode' => self::FILE_DELETE,
                    'source' => null,
                    'destination' => $destinationPath . $file,
                ];
            }
        }

        return $changes;
    }

    /**
     * Generates a list of files for the given path. If the optional argument $hashes is set, the hash of every file
     * will be checked.
     *
     * @param string $path
     * @param array $hashes
     * @return array
     *
     * @throws \Mosparo\Exception Hash of file "{FILE}" is not correct.
     */
    protected function getFileList(string $path, array $hashes = []): array
    {
        $checkHash = !empty($hashes);
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        foreach ($iterator as $file) {
            if ($file->getFilename() === '.' || $file->getFilename() === '..' || $file->isLink() || $file->isDir()) {
                continue;
            }

            $relativeFilePath = substr($file->getPathname(), strlen($path));
            $ignore = $this->ignoreFile($relativeFilePath);

            if (!$ignore) {
                if ($checkHash) {
                    $hash = $this->createFileHash($file->getPathname(), 'sha256');
                    $sourceHash = $hashes[$relativeFilePath] ?? false;

                    if ($sourceHash === false || $hash !== $sourceHash) {
                        throw new Exception(sprintf('Hash of file "%s" is not correct.', $relativeFilePath));
                    }
                }

                $files[] = $relativeFilePath;
            }
        }

        return $files;
    }

    /**
     * Returns true if a file should be ignored.
     *
     * @param string $filePath
     * @return bool
     */
    protected function ignoreFile(string $filePath): bool
    {
        $ignoredDirectories = [
            '/var',
            '/config/env.mosparo.php',
        ];

        foreach ($ignoredDirectories as $dir) {
            if (strpos($filePath, $dir) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if an additional file in a directory should be ignored.
     * Example: .well-known directory in the /public/ directory should not be deleted while updating
     *
     * @param string $filePath
     * @return bool
     */
    protected function ignoreInDestinationExistingFiles(string $filePath): bool
    {
        $ignoredDirectories = [
            [
                'path' => '/public',
                'exclude' => [
                    '/public/build',
                    '/public/bundles',
                ],
            ],
        ];

        foreach ($ignoredDirectories as $ignoredDirectory) {
            if (strpos($filePath, $ignoredDirectory['path']) === 0) {
                foreach ($ignoredDirectory['exclude'] as $excludeDir) {
                    if (strpos($filePath, $excludeDir) === 0) {
                        return false;
                    }
                }

                return true;
            }
        }

        // Ignore all dot files and directories in the root directory. If another softwar creates a
        // dot file or directory, an update would otherwise remove the file or directory.
        $firstTwoChars = substr($filePath, 0, 2);
        if ($firstTwoChars == '/.') {
            return true;
        }

        return false;
    }

    /**
     * Simulates the calculated changes and checks if all files and directories are writable or not.
     *
     * @param array $changes
     * @throws \Mosparo\Exception File "{FILE}" is not writable.
     */
    protected function simulateChanges(array $changes)
    {
        clearstatcache();

        $this->writableDirectories = [];

        foreach ($changes as $change) {
            $destinationPath = $change['destination'];

            $directory = dirname($destinationPath);
            $isDirectoryWritable = in_array($directory, $this->writableDirectories);
            if (!$isDirectoryWritable) {
                $isDirectoryWritable = $this->isDirectoryAvailable($directory);
            }

            if ($isDirectoryWritable && $this->fileSystem->exists($destinationPath) && !is_writable($destinationPath)) {
                throw new Exception(sprintf('File "%s" is not writable.', $destinationPath));
            }
        }
    }

    /**
     * Returns true if the given directory is available or creatable, if it does not exist and the parent directory
     * is writable.
     *
     * @param string $directory
     * @return bool
     *
     * @throws \Mosparo\Exception Directory "{DIRECTORY}" does not exist or is not writable.
     */
    protected function isDirectoryAvailable(string $directory): bool
    {
        // Gone too far, cannot handle anymore and this will not be writable anyway
        if (!$directory || $directory === '.') {
            return false;
        }

        if (in_array($directory, $this->writableDirectories)) {
            return true;
        }

        if (!$this->fileSystem->exists($directory)) {
            $writable = $this->isDirectoryAvailable(dirname($directory));
        } else {
            $writable = is_writable($directory);
        }

        if (!$writable) {
            throw new Exception(sprintf('Directory "%s" does not exist or is not writable.', $directory));
        }

        $this->writableDirectories[] = $directory;

        return true;
    }

    /**
     * Executes the calculated changes
     *
     * @param array $changes
     */
    protected function executeChanges(array $changes)
    {
        clearstatcache();
        $opcacheEnabled = function_exists('opcache_is_script_cached');

        foreach ($changes as $change) {
            $type = $change['mode'];
            $sourcePath = $change['source'];
            $destinationPath = $change['destination'];

            // If opcache is available, invalidate the cache for file we will copy or remove
            if ($opcacheEnabled && opcache_is_script_cached($destinationPath)) {
                opcache_invalidate($destinationPath, true);
            }

            if ($type === self::FILE_COPY) {
                if (!$this->fileSystem->exists(dirname($destinationPath))) {
                    $this->fileSystem->mkdir(dirname($destinationPath));
                }

                $this->fileSystem->copy($sourcePath, $destinationPath, true);
            } else if ($type === self::FILE_DELETE) {
                $this->fileSystem->remove($destinationPath);
            }
        }
    }

    /**
     * Validates the copied files with the hashes from the update service to make sure that everything worked correctly.
     *
     * @param string $destinationPath
     * @param array $changes
     * @param array $hashes
     *
     * @throws \Mosparo\Exception No hash found for file "{FILE}".
     * @throws \Mosparo\Exception Hash not valid for file "{FILE}".
     */
    protected function validateUpdatedFiles(string $destinationPath, array $changes, array $hashes)
    {
        clearstatcache();

        $destinationPath = rtrim($destinationPath, '/');

        foreach ($changes as $change) {
            // Skip all non-copy changes
            if ($change['mode'] !== self::FILE_COPY) {
                continue;
            }

            $filePath = $change['destination'];
            $relativeFilePath = substr($filePath, strlen($destinationPath));

            if (!isset($hashes[$relativeFilePath])) {
                throw new Exception(sprintf('No hash found for file "%s".', $relativeFilePath));
            }

            $sourceHash = $hashes[$relativeFilePath];
            $destinationHash = $this->createFileHash($filePath, 'sha256');

            if ($sourceHash !== $destinationHash) {
                throw new Exception(sprintf('Hash not valid for file "%s".', $relativeFilePath));
            }
        }
    }

    /**
     * Deletes the downloaded update file and the extracted update data
     *
     * @param string $filePath
     * @param string $sourcePath
     */
    protected function cleanup(string $filePath, string $sourcePath)
    {
        $this->fileSystem->remove([
            $filePath,
            $sourcePath,
        ]);

        // Clear the cache
        $directoryIterator = new \DirectoryIterator($this->cacheDirectory);
        foreach ($directoryIterator as $item) {
            // We cannot delete the cached Container because that leads to missing classes, so we exclude these files
            // from this custom cache clear method.
            if ($item->isDot() || strpos($item->getPathname(), '/Container') !== false) {
                continue;
            }

            $this->fileSystem->remove($item->getPathname());
        }
    }
}