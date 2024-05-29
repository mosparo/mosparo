<?php

namespace Mosparo\Controller\Administration;

use Mosparo\Helper\ConfigHelper;
use Mosparo\Util\TokenGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/administration/cron-jobs')]
class CronJobsController extends AbstractController
{
    protected ConfigHelper $configHelper;

    protected TranslatorInterface $translator;

    public function __construct(ConfigHelper $configHelper, TranslatorInterface $translator)
    {
        $this->configHelper = $configHelper;
        $this->translator = $translator;
    }

    #[Route('/', name: 'administration_cron_jobs_settings')]
    public function settings(Request $request): Response
    {
        $config = [
            'webCronJobActive' => $this->configHelper->getEnvironmentConfigValue('webCronJobActive', false),
            'webCronJobSecretKey' => $this->configHelper->getEnvironmentConfigValue('webCronJobSecretKey', (new TokenGenerator())->generateToken()),
            'webCronJobInterval' => $this->configHelper->getEnvironmentConfigValue('webCronJobInterval', 10),
        ];

        $isGeoIp2Active = ($this->configHelper->getEnvironmentConfigValue('geoipActive'));
        if ($isGeoIp2Active) {
            $config['geoIp2RefreshInterval'] = $this->configHelper->getEnvironmentConfigValue('geoIp2RefreshInterval', 7);
        }

        $formBuilder = $this->createFormBuilder($config, ['translation_domain' => 'mosparo'])
            ->add('webCronJobActive', CheckboxType::class, ['label' => 'administration.cronJobs.settings.enableWebCronJobs', 'required' => false])
            ->add('webCronJobSecretKey', TextType::class, [
                'label' => 'administration.cronJobs.settings.webCronJobSecretKey',
                'help' => 'administration.cronJobs.settings.webCronJobSecretKeyHelp',
                'required' => true
            ])
            ->add('webCronJobInterval', IntegerType::class, [
                'label' => 'administration.cronJobs.settings.webCronJobInterval',
                'help' => 'administration.cronJobs.settings.webCronJobIntervalHelp',
                'required' => true,
                'attr' => ['min' => 5],
                'constraints' => [
                    new GreaterThanOrEqual([
                        'value' => 5,
                        'message' => 'webCronJob.webCronJobIntervalAtLeast5Minutes'
                    ]),
                ],
            ]);

        if ($isGeoIp2Active) {
            $formBuilder->add('geoIp2RefreshInterval', IntegerType::class, [
                'label' => 'administration.cronJobs.settings.geoIp2RefreshInterval',
                'help' => 'administration.cronJobs.settings.geoIp2RefreshIntervalHelp',
                'required' => true,
                'attr' => ['min' => 1],
                'constraints' => [
                    new GreaterThanOrEqual([
                        'value' => 1,
                        'message' => 'webCronJob.geoIpRefreshIntervalAtLeast1Day'
                    ]),
                ],
            ]);
        }

        $form = $formBuilder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Save the config value
            $webCronjobActive = $form->get('webCronJobActive')->getData();
            $configChanges = [
                'webCronJobActive' => $webCronjobActive,
            ];

            if ($webCronjobActive) {
                $configChanges['webCronJobSecretKey'] = $form->get('webCronJobSecretKey')->getData();
                $configChanges['webCronJobInterval'] = $form->get('webCronJobInterval')->getData();

                if ($isGeoIp2Active) {
                    $configChanges['geoIp2RefreshInterval'] = $form->get('geoIp2RefreshInterval')->getData();
                }
            }

            $this->configHelper->writeEnvironmentConfig($configChanges);

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'administration.cronJobs.settings.message.savedSuccessfully',
                    [],
                    'mosparo'
                )
            );

            return $this->redirectToRoute('administration_cron_jobs_settings');
        }

        return $this->render('administration/cronjobs/settings.html.twig', [
            'form' => $form->createView(),
            'isWebCronJobActive' => ($this->configHelper->getEnvironmentConfigValue('webCronJobActive', false)),
            'webCronJobSecretKey' => $config['webCronJobSecretKey'] ?? '',
            'webCronJobInterval' => $config['webCronJobInterval'] ?? 10,
            'isGeoIp2Active' => ($this->configHelper->getEnvironmentConfigValue('geoipActive', false))
        ]);
    }
}
