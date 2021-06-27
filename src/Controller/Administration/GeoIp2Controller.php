<?php

namespace Mosparo\Controller\Administration;

use Mosparo\Entity\Project;
use Mosparo\Entity\ProjectMember;
use Mosparo\Entity\User;
use Mosparo\Form\PasswordFormType;
use Mosparo\Form\RuleAddMultipleItemsType;
use Mosparo\Form\RuleFormType;
use Mosparo\Helper\ConfigHelper;
use Mosparo\Helper\GeoIp2Helper;
use Mosparo\Repository\RuleRepository;
use Mosparo\Rule\RuleTypeManager;
use Mosparo\Util\TokenGenerator;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\BoolColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/administration/geoip2")
 */
class GeoIp2Controller extends AbstractController
{
    protected $configHelper;

    protected $geoIp2Helper;

    public function __construct(ConfigHelper $configHelper, GeoIp2Helper $geoIp2Helper)
    {
        $this->configHelper = $configHelper;
        $this->geoIp2Helper = $geoIp2Helper;
    }

    /**
     * @Route("/", name="administration_geoip2_settings")
     */
    public function settings(Request $request, RuleRepository $ruleRepository): Response
    {
        $config = [
            'geoipActive' => (bool) $this->configHelper->getConfigValue('geoipActive', false),
            'geoipLicenseKey' => $this->configHelper->getConfigValue('geoipLicenseKey', '')
        ];
        $form = $this->createFormBuilder($config)
            ->add('geoipActive', CheckboxType::class, ['label' => 'Use the automatic IP address localization', 'required' => false])
            ->add('geoipLicenseKey', TextType::class, ['label' => 'License Key', 'required' => false])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            // Save the config value
            $this->configHelper->setConfigValue('geoipActive', $form->get('geoipActive')->getData());
            $this->configHelper->setConfigValue('geoipLicenseKey', $form->get('geoipLicenseKey')->getData());

            $session = $request->getSession();
            $session->getFlashBag()->add('success', 'The settings were saved successfully.');

            return $this->redirectToRoute('administration_geoip2_settings');
        }

        return $this->render('administration/geoip2/settings.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/download", name="administration_geoip2_download")
     */
    public function download(Request $request): Response
    {
        $result = $this->geoIp2Helper->downloadDatabase();

        $session = $request->getSession();
        if ($result === true) {
            $session->getFlashBag()->add('success', 'The database was downloaded successfully.');
        } else {
            $session->getFlashBag()->add('error', 'An error occurred while mosparo tried to download the database.' . implode(' ', $result));
        }

        return $this->redirectToRoute('administration_geoip2_settings');
    }
}
