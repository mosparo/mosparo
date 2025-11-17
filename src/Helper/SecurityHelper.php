<?php

namespace Mosparo\Helper;

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Kir\StringUtils\Matching\Wildcards\Pattern;
use Mosparo\Entity\Delay;
use Mosparo\Entity\IpLocalization;
use Mosparo\Entity\Lockout;
use Mosparo\Entity\SecurityGuideline;
use Mosparo\Util\HashUtil;
use Mosparo\Util\IpUtil;

class SecurityHelper
{
    const FEATURE_DELAY = 1;
    const FEATURE_LOCKOUT = 2;

    protected EntityManagerInterface $entityManager;

    protected ProjectHelper $projectHelper;

    protected GeoIp2Helper $geoIp2Helper;

    public function __construct(EntityManagerInterface $entityManager, ProjectHelper $projectHelper, GeoIp2Helper $geoIp2Helper)
    {
        $this->entityManager = $entityManager;
        $this->projectHelper = $projectHelper;
        $this->geoIp2Helper = $geoIp2Helper;
    }

    public function checkIpAddress(string $ipAddress, int $feature, array $securitySettings)
    {
        if (trim($securitySettings['ipAllowList']) && IpUtil::isIpAllowed($ipAddress, $securitySettings['ipAllowList'])) {
            return false;
        }

        if ($feature === self::FEATURE_DELAY) {
            $delayActive = $securitySettings['delayActive'];

            if ($delayActive) {
                $delay = $this->checkForDelay($ipAddress, $securitySettings);

                if ($delay !== null) {
                    return $delay;
                }
            }
        } else if ($feature === self::FEATURE_LOCKOUT) {
            $lockoutActive = $securitySettings['lockoutActive'];

            if ($lockoutActive) {
                $lockout = $this->checkForLockout($ipAddress, $securitySettings);

                if ($lockout !== null) {
                    return $lockout;
                }
            }
        }

        return false;
    }

    protected function checkForDelay(string $ipAddress, array $securitySettings)
    {
        $existingDelay = $this->checkForExistingDelay($ipAddress);

        $delayNumberOfRequests = $securitySettings['delayNumberOfRequests'];
        $delayDetectionTimeFrame = $securitySettings['delayDetectionTimeFrame'];
        $delayTime = $securitySettings['delayTime'];
        $delayMultiplicator = $securitySettings['delayMultiplicator'];

        $count = $this->countRequests($ipAddress, $delayDetectionTimeFrame);
        if ($count > $delayNumberOfRequests || $existingDelay !== null) {
            if ($existingDelay === null) {
                $delay = new Delay();
                $delay->setIpAddress($ipAddress);
                $delay->setStartedAt(new DateTime());
                $delay->setDuration($delayTime);

                $this->entityManager->persist($delay);
            } else {
                $delay = $existingDelay;
                $delay->setDuration($delay->getDuration() * $delayMultiplicator);
            }

            $endDateTime = clone $delay->getStartedAt();
            $endDateTime->add(new DateInterval('PT' . $delay->getDuration() . 'S'));

            $delay->setValidUntil($endDateTime);
            $this->entityManager->flush();

            return $delay;
        }

        return null;
    }

    protected function checkForLockout(string $ipAddress, array $securitySettings)
    {
        $existingLockout = $this->checkForExistingLockout($ipAddress);

        $lockoutNumberOfRequests = $securitySettings['lockoutNumberOfRequests'];
        $lockoutDetectionTimeFrame = $securitySettings['lockoutDetectionTimeFrame'];
        $lockoutTime = $securitySettings['lockoutTime'];
        $lockoutMultiplicator = $securitySettings['lockoutMultiplicator'];

        $count = $this->countRequests($ipAddress, $lockoutDetectionTimeFrame);
        if ($count > $lockoutNumberOfRequests || $existingLockout !== null) {
            if ($existingLockout === null) {
                $lockout = new Lockout();
                $lockout->setIpAddress($ipAddress);
                $lockout->setStartedAt(new DateTime());
                $lockout->setDuration($lockoutTime);

                $this->entityManager->persist($lockout);
            } else {
                $lockout = $existingLockout;
                $lockout->setDuration($lockout->getDuration() * $lockoutMultiplicator);
            }

            $endDateTime = $lockout->getStartedAt();
            $endDateTime->add(new DateInterval('PT' . $lockout->getDuration() . 'S'));

            $lockout->setValidUntil($endDateTime);
            $this->entityManager->flush();

            return $lockout;
        }

        return null;
    }

    public function countRequests($ipAddress, $timeFrame)
    {
        $startTime = new DateTime();
        $startTime->sub(new DateInterval('PT' . $timeFrame . 'S'));

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('count(st.id) AS requests')
           ->from('Mosparo\Entity\SubmitToken', 'st')
           ->leftJoin('Mosparo\Entity\Submission', 's', 'WITH', 'st.id = s.submitToken')
           ->where('st.ipAddress = :ip')
           ->andWhere('st.createdAt > :startTime')
           ->setParameter(':ip', HashUtil::hash($ipAddress))
           ->setParameter(':startTime', $startTime);

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result['requests'] ?? 0;
    }

    public function countRequestsInTimeFrame($timeFrame)
    {
        $startTime = new DateTime();
        $startTime->sub(new DateInterval('PT' . $timeFrame . 'S'));

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('count(st.id) AS requests')
            ->from('Mosparo\Entity\SubmitToken', 'st')
            ->leftJoin('Mosparo\Entity\Submission', 's', 'WITH', 'st.id = s.submitToken')
            ->andWhere('st.createdAt > :startTime')
            ->setParameter(':startTime', $startTime);

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result['requests'] ?? 0;
    }

    protected function checkForExistingLockout($ipAddress)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('l')
           ->from('Mosparo\Entity\Lockout', 'l')
           ->where('l.ipAddress = :ip')
           ->andWhere('l.validUntil > :now')
           ->setMaxResults(1)
           ->setParameter('ip', HashUtil::hash($ipAddress))
           ->setParameter('now', new DateTime());

        return $qb->getQuery()->getOneOrNullResult();
    }

    protected function checkForExistingDelay($ipAddress)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('d')
           ->from('Mosparo\Entity\Delay', 'd')
           ->where('d.ipAddress = :ip')
           ->andWhere('d.validUntil > :now')
           ->setMaxResults(1)
           ->setParameter('ip', HashUtil::hash($ipAddress))
           ->setParameter('now', new DateTime());

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function countEqualSubmissions(string $formSignature, int $timeFrame, bool $basedOnIp, ?string $ipAddress): int
    {
        $startTime = (new DateTime())->sub(new DateInterval(sprintf('PT%dS', $timeFrame)));

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(s.id) AS submissions')
           ->from('Mosparo\Entity\SubmitToken', 'st')
           ->leftJoin('Mosparo\Entity\Submission', 's', 'WITH', 'st.id = s.submitToken')
           ->where('s.signature = :formSignature')
           ->andWhere('st.createdAt > :startTime')
           ->setParameter('startTime', $startTime)
           ->setParameter('formSignature', $formSignature);

        if ($basedOnIp && $ipAddress) {
            $qb->andWhere('st.ipAddress = :ip')
               ->setParameter('ip', HashUtil::hash($ipAddress));
        }

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result['submissions'] ?? 0;
    }

    public function determineSecuritySettings(?string $ipAddress, array $formOriginData): array
    {
        $ipLocalization = null;
        if ($ipAddress) {
            $ipLocalization = $this->geoIp2Helper->locateIpAddress($ipAddress);
            if ($ipLocalization === false) {
                $ipLocalization = null;
            }
        }

        $builder = $this->entityManager->createQueryBuilder();
        $builder
            ->select('sg')
            ->from(SecurityGuideline::class, 'sg')
            ->orderBy('sg.priority', 'DESC');

        foreach ($builder->getQuery()->getResult() as $securityGuideline) {
            if ($this->matchSecurityGuideline($securityGuideline, $ipAddress, $formOriginData, $ipLocalization)) {
                return $securityGuideline->getConfigValues();
            }
        }

        $project = $this->projectHelper->getActiveProject();

        return $project->getSecurityConfigValues();
    }

    protected function matchSecurityGuideline(SecurityGuideline $securityGuideline, ?string $ipAddress, array $formOriginData, ?IpLocalization $ipLocalization = null): bool
    {
        $ipMatch = null;
        $formOriginMatch = null;

        if ($securityGuideline->getSubnets() || $securityGuideline->getAsNumbers() || $securityGuideline->getCountryCodes()) {
            $ipMatch = $this->matchSecurityGuidelineForIpAddress($securityGuideline, $ipAddress, $ipLocalization);
        }

        if ($securityGuideline->getFormPageUrls() || $securityGuideline->getFormActionUrls() || $securityGuideline->getFormIds()) {
            $formOriginMatch = $this->matchSecurityGuidelineForFormOrigin($securityGuideline, $formOriginData);
        }

        if ($ipMatch && $formOriginMatch) {
            return true;
        } else if ($ipMatch && $formOriginMatch === null) {
            return true;
        } if ($ipMatch === null && $formOriginMatch) {
            return true;
        } else {
            return false;
        }
    }

    protected function matchSecurityGuidelineForIpAddress(SecurityGuideline $securityGuideline, ?string $ipAddress, ?IpLocalization $ipLocalization = null): bool
    {
        foreach ($securityGuideline->getSubnets() as $subnet) {
            if (IpUtil::isIpInSubnet($subnet, $ipAddress)) {
                return true;
            }
        }

        if ($ipLocalization !== null) {
            if (in_array($ipLocalization->getCountry(), $securityGuideline->getCountryCodes())) {
                return true;
            }

            if (in_array($ipLocalization->getAsNumber(), $securityGuideline->getAsNumbers())) {
                return true;
            }
        }

        return false;
    }

    protected function matchSecurityGuidelineForFormOrigin(SecurityGuideline $securityGuideline, array $formOriginData): bool
    {
        $formPageUrl = $formOriginData['pageUrl'] ?? '';
        foreach ($securityGuideline->getFormPageUrls() as $fPageUrl) {
            if ($this->matchUrl($fPageUrl, $formPageUrl)) {
                return true;
            }
        }

        $formActionUrl = $formOriginData['formActionUrl'] ?? '';
        foreach ($securityGuideline->getFormActionUrls() as $fActionUrl) {
            if ($this->matchUrl($fActionUrl, $formActionUrl)) {
                return true;
            }
        }

        $formId = $formOriginData['formId'] ?? '';
        if (in_array($formId, $securityGuideline->getFormIds())) {
            return true;
        }

        return false;
    }

    protected function matchUrl(string $pattern, string $url): bool
    {
        if ($url === '') {
            return false;
        }

        // If the pattern not contains an asterisk and the URL starts with the pattern, we have a match.
        if (!str_contains($pattern, '*') && str_starts_with($url, $pattern)) {
            return true;
        }

        // Otherwise, use the pattern class to match the URL
        if (Pattern::create($pattern)->match($url)) {
            return true;
        }

        return false;
    }
}