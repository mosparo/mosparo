<?php

namespace Mosparo\Controller;

use DateTime;
use DateInterval;
use Mosparo\Enum\CleanupExecutor;
use Mosparo\Helper\CleanupHelper;
use Mosparo\Helper\ConfigHelper;
use Mosparo\Helper\GeoIp2Helper;
use Mosparo\Helper\ProjectHelper;
use Mosparo\Helper\RulePackageHelper;
use Mosparo\Util\IpUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class WebCronJobController extends AbstractController
{
    protected ConfigHelper $configHelper;

    protected ProjectHelper $projectHelper;

    protected CleanupHelper $cleanupHelper;

    protected RulePackageHelper $rulePackageHelper;

    protected GeoIp2Helper $geoIp2Helper;

    public function __construct(ConfigHelper $configHelper, ProjectHelper $projectHelper, CleanupHelper $cleanupHelper, RulePackageHelper $rulePackageHelper, GeoIp2Helper $geoIp2Helper)
    {
        $this->configHelper = $configHelper;
        $this->projectHelper = $projectHelper;
        $this->cleanupHelper = $cleanupHelper;
        $this->rulePackageHelper = $rulePackageHelper;
        $this->geoIp2Helper = $geoIp2Helper;
    }

    #[Route('/cron-jobs/execute', name: 'cron_jobs_execute')]
    public function executeCronJobs(Request $request): Response
    {
        // Check if the IP is allowed to access the web cron jobs
        $webCronJobAccessIpAllowList = $this->configHelper->getEnvironmentConfigValue('web_cron_job_access_ip_allow_list','');
        if (!IpUtil::isIpAllowed($request->getClientIp(), $webCronJobAccessIpAllowList)) {
            throw new AccessDeniedHttpException(sprintf('Access to the web cron jobs for this IP address (%s) is not allowed.', $request->getClientIp()));
        }

        $key = $request->query->get('key');
        if ($key === null) {
            return new Response('400 Bad Request', 400);
        }

        $webCronJobActive = $this->configHelper->getEnvironmentConfigValue('webCronJobActive');
        if (!$webCronJobActive) {
            return new response('403 Forbidden', 403);
        }

        $webCronJobSecretKey  = $this->configHelper->getEnvironmentConfigValue('webCronJobSecretKey', '');
        if ($key !== $webCronJobSecretKey) {
            return new response('401 Unauthorized', 401);
        }

        // Check the last cron job execution
        $cache = new FilesystemAdapter();
        $nextCronJob = $cache->getItem('mosparoNextCronJob');
        $cronJobStartedAt = $cache->getItem('mosparoCronJobStartedAt');

        if ($nextCronJob->get() !== null) {
            // Return, if the next cron job date is in the future
            if ($nextCronJob->get() > new DateTime()) {
                return new Response('425 Too Early', 425);
            }

            // Do not start the cron job if another request is already executing the cron job
            if ($cronJobStartedAt->get() !== null && $cronJobStartedAt->get() > (new DateTime())->sub(new DateInterval('PT5M'))) {
                return new Response('429 Too Many Requests', 429);
            }
        }

        // Disable the project related filter
        $this->projectHelper->unsetActiveProject();

        // Lock the cron job execution
        $cronJobStartedAt->set(new DateTime());
        $cache->save($cronJobStartedAt);

        // Get the configured max exeuction time
        $maxExecutionTime = intval(ini_get('max_execution_time'));
        if (!$maxExecutionTime) {
            $maxExecutionTime = 30;
        }

        // Use only 80% of the maximum execution time for the cleanup so the request does not fail.
        $maxTimeCleanup = floor($maxExecutionTime * 0.8);
        $start = time();

        // Execute the cleanup process
        $this->cleanupHelper->cleanup(1000000, true, false, $maxTimeCleanup, CleanupExecutor::WEB_CRON_JOB);

        // Download the rule packages, only execute this if we have more than 10% of the max execution time available.
        if ((time() - $start) < ($maxExecutionTime * 0.9)) {
            $this->rulePackageHelper->fetchAll();
        }

        // Update the GeoIP2 database, only execute this if we have more than 10% of the max execution time available.
        if ((time() - $start) < ($maxExecutionTime * 0.9)) {
            $isGeoIp2Active = ($this->configHelper->getEnvironmentConfigValue('geoipActive'));
            $nextGeoIp2Refresh = $cache->getItem('mosparoGeoIp2LastCronJobRefresh');

            if ($isGeoIp2Active && ($nextGeoIp2Refresh->get() === null || $nextGeoIp2Refresh->get() > new DateTime())) {
                $this->geoIp2Helper->downloadDatabase();

                $geoIp2RefreshInterval = $this->configHelper->getEnvironmentConfigValue('geoIp2RefreshInterval', 7);
                $nextGeoIp2RefreshDate = (new DateTime())->add(new DateInterval(sprintf('P%dD', $geoIp2RefreshInterval)));
                $nextGeoIp2Refresh->set($nextGeoIp2RefreshDate);
                $cache->save($nextGeoIp2Refresh);
            }
        }

        // Plan the next cron job
        $webCronJobInterval = $this->configHelper->getEnvironmentConfigValue('webCronJobInterval', 10);
        $nextCronJobDate = (new DateTime())->add(new DateInterval(sprintf('PT%dM', $webCronJobInterval)));
        $nextCronJob->set($nextCronJobDate);
        $cache->save($nextCronJob);

        $cronJobStartedAt->set(null);
        $cache->save($cronJobStartedAt);

        return new Response('200 OK', 200);
    }
}
