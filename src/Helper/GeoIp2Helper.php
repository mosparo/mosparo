<?php

namespace Mosparo\Helper;

use Doctrine\ORM\EntityManagerInterface;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Mosparo\Entity\IpLocalization;
use Mosparo\Util\HashUtil;

class GeoIp2Helper
{
    protected $entityManager;

    protected $configHelper;

    protected $cleanupHelper;

    protected $downloadDirectory;

    public function __construct(EntityManagerInterface $entityManager, ConfigHelper $configHelper, CleanupHelper $cleanupHelper, $downloadDirectory)
    {
        $this->entityManager = $entityManager;
        $this->configHelper = $configHelper;
        $this->cleanupHelper = $cleanupHelper;
        $this->downloadDirectory = $downloadDirectory;
    }

    public function downloadDatabase()
    {
        $licenseKey = $this->configHelper->getConfigValue('geoipLicenseKey', '');
        if (trim($licenseKey) === '') {
            return false;
        }

        $this->cleanupHelper->cleanupIpLocalizationCache();

        $client = new \tronovav\GeoIP2Update\Client(array(
            'license_key' => $licenseKey,
            'dir' => $this->downloadDirectory,
            'editions' => array('GeoLite2-ASN', 'GeoLite2-Country'),
        ));
        $client->run();

        if ($client->errors()) {
            return $client->getErrors();
        }

        return true;
    }

    public function locateIpAddress($ipAddress)
    {
        $geoipActive = $this->configHelper->getConfigValue('geoipActive', false);
        if (!$geoipActive) {
            return false;
        }

        $ipLocalizationRepository = $this->entityManager->getRepository(IpLocalization::class);
        $ipLocalization = $ipLocalizationRepository->findOneBy(['ipAddress' => HashUtil::hash($ipAddress)]);
        if ($ipLocalization !== null) {
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
        } catch (AddressNotFoundException $e) {
            // Do nothing
        }

        // Locate the country
        try {
            $countryReader = new Reader($this->downloadDirectory . '/GeoLite2-Country/GeoLite2-Country.mmdb');
            $country = $countryReader->country($ipAddress);

            $ipLocalization->setCountry($country->country->isoCode);
        } catch (AddressNotFoundException $e) {
            // Do nothing
        }

        $this->entityManager->persist($ipLocalization);
        $this->entityManager->flush();

        return $ipLocalization;
    }
}