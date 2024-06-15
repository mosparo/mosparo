<?php

namespace Mosparo\Helper;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GeoIp2\Database\Reader;
use Mosparo\Entity\IpLocalization;
use Mosparo\Util\HashUtil;
use Mosparo\Util\PathUtil;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use danielsreichenbach\GeoIP2Update\Client;

class GeoIp2Helper
{
    protected EntityManagerInterface $entityManager;

    protected ConfigHelper $configHelper;

    protected CleanupHelper $cleanupHelper;

    protected Filesystem $filesystem;

    protected string $downloadDirectory;

    protected array $localizedIpAddresses = [];

    public function __construct(EntityManagerInterface $entityManager, ConfigHelper $configHelper, CleanupHelper $cleanupHelper, Filesystem $filesystem, string $downloadDirectory)
    {
        $this->entityManager = $entityManager;
        $this->configHelper = $configHelper;
        $this->cleanupHelper = $cleanupHelper;
        $this->filesystem = $filesystem;
        $this->downloadDirectory = PathUtil::prepareFilePath($downloadDirectory);
    }

    public function isGeoIp2Active()
    {
        return $this->configHelper->getEnvironmentConfigValue('geoipActive', false);
    }

    public function downloadDatabase()
    {
        $accountId = $this->configHelper->getEnvironmentConfigValue('geoipAccountId', '');
        $licenseKey = $this->configHelper->getEnvironmentConfigValue('geoipLicenseKey', '');
        if (!trim($accountId) || !trim($licenseKey)) {
            return [
                'Account ID or license key not specified.',
            ];
        }

        $this->cleanupHelper->cleanupIpLocalizationCache();

        // Create the directory if it does not exist already
        if (!$this->filesystem->exists($this->downloadDirectory)) {
            try {
                $this->filesystem->mkdir($this->downloadDirectory);
            } catch (IOException $e) {
                return [
                    $e->getMessage()
                ];
            }
        }

        $client = new Client(array(
            'account_id' => $accountId,
            'license_key' => $licenseKey,
            'dir' => $this->downloadDirectory,
            'editions' => array('GeoLite2-ASN', 'GeoLite2-Country'),
        ));
        $client->run();

        if ($client->errors()) {
            return $client->errors();
        }

        return true;
    }

    public function getDatabaseInformations(): array
    {
        $versions = ['GeoLite2-ASN' => false, 'GeoLite2-Country' => false];
        try {
            $asReader = new Reader($this->downloadDirectory . '/GeoLite2-ASN/GeoLite2-ASN.mmdb');
            $metadata = $asReader->metadata();
            $versions['GeoLite2-ASN'] = (new DateTime())->setTimestamp($metadata->buildEpoch);
        } catch (Exception $e) {
            // Do nothing
        }

        try {
            $countryReader = new Reader($this->downloadDirectory . '/GeoLite2-Country/GeoLite2-Country.mmdb');
            $metadata = $countryReader->metadata();
            $versions['GeoLite2-Country'] = (new DateTime())->setTimestamp($metadata->buildEpoch);
        } catch (Exception $e) {
            // Do nothing
        }

        return $versions;
    }

    public function locateIpAddress($ipAddress)
    {
        $geoipActive = $this->configHelper->getEnvironmentConfigValue('geoipActive', false);
        if (!$geoipActive) {
            return false;
        }

        $ipAddressHash = HashUtil::hash($ipAddress);
        if (isset($this->localizedIpAddresses[$ipAddressHash])) {
            return $this->localizedIpAddresses[$ipAddressHash];
        }

        $ipLocalizationRepository = $this->entityManager->getRepository(IpLocalization::class);
        $ipLocalization = $ipLocalizationRepository->findOneBy(['ipAddress' => $ipAddressHash]);
        if ($ipLocalization !== null) {
            $this->localizedIpAddresses[$ipAddressHash] = $ipLocalization;
            return $ipLocalization;
        }

        $ipLocalization = new IpLocalization();
        $ipLocalization->setIpAddress($ipAddress);

        // Locate the AS number
        try {
            $asReader = new Reader($this->downloadDirectory . '/GeoLite2-ASN/GeoLite2-ASN.mmdb');
            $asn = $asReader->asn($ipAddress);

            $ipLocalization->setAsNumber($asn->autonomousSystemNumber);
            $ipLocalization->setAsOrganization($asn->autonomousSystemOrganization);
        } catch (Exception $e) {
            // Do nothing
        }

        // Locate the country
        try {
            $countryReader = new Reader($this->downloadDirectory . '/GeoLite2-Country/GeoLite2-Country.mmdb');
            $country = $countryReader->country($ipAddress);

            $ipLocalization->setCountry($country->country->isoCode);
        } catch (Exception $e) {
            // Do nothing
        }

        $this->entityManager->persist($ipLocalization);
        $this->entityManager->flush();

        $this->localizedIpAddresses[$ipAddressHash] = $ipLocalization;

        return $ipLocalization;
    }
}