<?php

namespace Mosparo\Helper;

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use IPLib\Factory;
use IPLib\Range\Subnet;
use Mosparo\Entity\Delay;
use Mosparo\Entity\Lockout;
use Mosparo\Entity\Project;
use Mosparo\Util\HashUtil;

class SecurityHelper
{
    const FEATURE_DELAY = 1;
    const FEATURE_LOCKOUT = 2;

    protected EntityManagerInterface $entityManager;

    protected ProjectHelper $projectHelper;

    public function __construct(EntityManagerInterface $entityManager, ProjectHelper $projectHelper)
    {
        $this->entityManager = $entityManager;
        $this->projectHelper = $projectHelper;
    }

    public function checkIpAddress($ipAddress, $feature)
    {
        $project = $this->projectHelper->getActiveProject();
        $ipAllowList = $project->getConfigValue('ipAllowList');

        if ($this->isIpOnAllowList($ipAddress, $ipAllowList)) {
            return false;
        }

        if ($feature === self::FEATURE_DELAY) {
            $delayActive = $project->getConfigValue('delayActive');

            if ($delayActive) {
                $delay = $this->checkForDelay($ipAddress, $project);

                if ($delay !== null) {
                    return $delay;
                }
            }
        } else if ($feature === self::FEATURE_LOCKOUT) {
            $lockoutActive = $project->getConfigValue('lockoutActive');

            if ($lockoutActive) {
                $lockout = $this->checkForLockout($ipAddress, $project);

                if ($lockout !== null) {
                    return $lockout;
                }
            }
        }

        return false;
    }

    protected function isIpOnAllowList($ipAddress, $ipAllowList): bool
    {
        $items = preg_split('/\r\n|\r|\n/', $ipAllowList);
        foreach ($items as $item) {
            if (strpos($item, '/') !== false) {
                $address = Factory::parseAddressString($ipAddress);
                $subnet = Subnet::parseString($item);

                if ($address !== null &&
                    $subnet !== null &&
                    $address->getAddressType() == $subnet->getAddressType() &&
                    $subnet->contains($address)
                ) {
                    return true;
                }
            } else if ($item === $ipAddress) {
                return true;
            }
        }

        return false;
    }

    protected function checkForDelay($ipAddress, Project $project)
    {
        $existingDelay = $this->checkForExistingDelay($ipAddress);

        $delayNumberOfRequests = $project->getConfigValue('delayNumberOfRequests');
        $delayDetectionTimeFrame = $project->getConfigValue('delayDetectionTimeFrame');
        $delayTime = $project->getConfigValue('delayTime');
        $delayMultiplicator = $project->getConfigValue('delayMultiplicator');

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

    protected function checkForLockout($ipAddress, Project $project)
    {
        $existingLockout = $this->checkForExistingLockout($ipAddress);

        $lockoutNumberOfRequests = $project->getConfigValue('lockoutNumberOfRequests');
        $lockoutDetectionTimeFrame = $project->getConfigValue('lockoutDetectionTimeFrame');
        $lockoutTime = $project->getConfigValue('lockoutTime');
        $lockoutMultiplicator = $project->getConfigValue('lockoutMultiplicator');

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
}