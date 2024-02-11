<?php

namespace Mosparo\Controller\Administration;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Helper\ConfigHelper;
use Mosparo\Helper\GeoIp2Helper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/administration/geoip2')]
class GeoIp2Controller extends AbstractController
{
    protected ConfigHelper $configHelper;

    protected GeoIp2Helper $geoIp2Helper;

    protected TranslatorInterface $translator;

    public function __construct(ConfigHelper $configHelper, GeoIp2Helper $geoIp2Helper, TranslatorInterface $translator)
    {
        $this->configHelper = $configHelper;
        $this->geoIp2Helper = $geoIp2Helper;
        $this->translator = $translator;
    }

    #[Route('/', name: 'administration_geoip2_settings')]
    public function settings(Request $request): Response
    {
        $config = [
            'geoipActive' => $this->configHelper->getEnvironmentConfigValue('geoipActive', false),
            'geoipLicenseKey' => $this->configHelper->getEnvironmentConfigValue('geoipLicenseKey', '')
        ];
        $form = $this->createFormBuilder($config, ['translation_domain' => 'mosparo'])
            ->add('geoipActive', CheckboxType::class, ['label' => 'administration.geoip2.settings.useGeoip2Field', 'required' => false])
            ->add('geoipLicenseKey', TextType::class, ['label' => 'administration.geoip2.settings.licenseKeyField', 'required' => false])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Save the config value
            $licenseKey = $form->get('geoipLicenseKey')->getData();
            if ($licenseKey === null) {
                $licenseKey = '';
            }

            $this->configHelper->writeEnvironmentConfig([
                'geoipActive' => $form->get('geoipActive')->getData(),
                'geoipLicenseKey' => $licenseKey
            ]);

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'administration.geoip2.settings.message.savedSuccessfully',
                    [],
                    'mosparo'
                )
            );

            return $this->redirectToRoute('administration_geoip2_settings');
        }

        $versions = $this->geoIp2Helper->getDatabaseInformations();
        $hasDatabase = !in_array(false, $versions);

        return $this->render('administration/geoip2/settings.html.twig', [
            'form' => $form->createView(),
            'isActive' => ($this->configHelper->getEnvironmentConfigValue('geoipActive', false)),
            'hasLicenseKey' => ($this->configHelper->getEnvironmentConfigValue('geoipLicenseKey', '')),
            'versions' => $versions,
            'hasDatabase' => $hasDatabase,
        ]);
    }

    #[Route('/download', name: 'administration_geoip2_download')]
    public function download(Request $request): Response
    {
        $session = $request->getSession();
        $licenseKey = $this->configHelper->getEnvironmentConfigValue('geoipLicenseKey', '');
        if ($licenseKey !== '') {
            $result = $this->geoIp2Helper->downloadDatabase();

            if ($result === true) {
                $session->getFlashBag()->add(
                    'success',
                    $this->translator->trans(
                        'administration.geoip2.downloadAndUpdate.web.message.successfullyDownloaded',
                        [],
                        'mosparo'
                    )
                );
            } else {
                $session->getFlashBag()->add(
                    'error',
                    $this->translator->trans(
                        'administration.geoip2.downloadAndUpdate.web.message.errorDownload',
                        ['%error%' => implode(' ', $result)],
                        'mosparo'
                    )
                );
            }
        } else {
            $session->getFlashBag()->add(
                'error',
                $this->translator->trans(
                    'administration.geoip2.downloadAndUpdate.web.message.specifyLicenseKey',
                    [],
                    'mosparo'
                )
            );
        }

        return $this->redirectToRoute('administration_geoip2_settings');
    }
}
