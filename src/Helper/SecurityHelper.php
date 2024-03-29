<?php

namespace Mosparo\Helper;

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
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
        if (IpUtil::isIpAllowed($ipAddress, $securitySettings['ipAllowList'])) {
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

    protected function countRequests($ipAddress, $timeFrame)
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

    public function determineSecuritySettings(?string $ipAddress): array
    {
        if ($ipAddress) {
            $ipLocalization = $this->geoIp2Helper->locateIpAddress($ipAddress);
            if ($ipLocalization === false) {
                $ipLocalization = null;
            }

            $builder = $this->entityManager->createQueryBuilder();
            $builder
                ->select('sg')
                ->from(SecurityGuideline::class, 'sg')
                ->orderBy('sg.priority', 'DESC');

            foreach ($builder->getQuery()->getResult() as $securityGuideline) {
                if ($this->matchSecurityGuideline($securityGuideline, $ipAddress, $ipLocalization)) {
                    return $securityGuideline->getConfigValues();
                }
            }
        }

        $project = $this->projectHelper->getActiveProject();

        return $project->getSecurityConfigValues();
    }

    protected function matchSecurityGuideline(SecurityGuideline $securityGuideline, string $ipAddress, ?IpLocalization $ipLocalization = null): bool
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
}