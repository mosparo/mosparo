<?php

namespace Mosparo\Controller\Administration;

use Mosparo\Helper\ConfigHelper;
use Mosparo\Helper\LocaleHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/administration/settings")
 */
class SettingsController extends AbstractController
{
    protected $configHelper;

    protected $translator;

    protected $localeHelper;

    public function __construct(ConfigHelper $configHelper, TranslatorInterface $translator, LocaleHelper $localeHelper)
    {
        $this->configHelper = $configHelper;
        $this->translator = $translator;
        $this->localeHelper = $localeHelper;
    }

    /**
     * @Route("/", name="administration_settings")
     */
    public function settings(Request $request): Response
    {
        $environmentConfig = $this->configHelper->readEnvironmentConfig();
        $config = [
            'mosparoName' => $environmentConfig['mosparo_name'] ?? '',

            'defaultLocale' => $environmentConfig['default_locale'] ?? 'en_US',
            'defaultDateFormat' => $environmentConfig['default_date_format'] ?? 'Y-m-d',
            'defaultTimeFormat' => $environmentConfig['default_time_format'] ?? 'H:i:s',
            'defaultTimezone' => $environmentConfig['default_timezone'] ?? 'UTC',

            'mailerUseSmtp' => (bool) ($environmentConfig['mailer_transport'] ?? '') == 'smtp',
            'mailerHost' => $environmentConfig['mailer_host'] ?? '',
            'mailerPort' => $environmentConfig['mailer_port'] ?? '25',
            'mailerUser' => $environmentConfig['mailer_user'] ?? '',
            'mailerPassword' => $environmentConfig['mailer_password'] ?? '',
            'mailerFromAddress' => $environmentConfig['mailer_from_address'] ?? '',
            'mailerFromName' => $environmentConfig['mailer_from_name'] ?? '',
        ];
        $form = $this->createFormBuilder($config, ['translation_domain' => 'mosparo'])
            ->add('mosparoName', TextType::class, ['label' => 'administration.settings.mainSettings.form.mosparoName'])
            ->add('defaultLocale', ChoiceType::class, ['label' => 'administration.settings.localeSettings.form.defaultLocale', 'choices' => $this->localeHelper->findAvailableLanguages(), 'attr' => ['class' => 'form-select']])
            ->add('defaultDateFormat', ChoiceType::class, ['label' => 'administration.settings.localeSettings.form.defaultDateFormat', 'choices' => $this->localeHelper->getDateFormats(), 'attr' => ['class' => 'form-select']])
            ->add('defaultTimeFormat', ChoiceType::class, ['label' => 'administration.settings.localeSettings.form.defaultTimeFormat', 'choices' => $this->localeHelper->getTimeFormats(), 'attr' => ['class' => 'form-select']])
            ->add('defaultTimezone', TimezoneType::class, ['label' => 'administration.settings.localeSettings.form.defaultTimezone', 'attr' => ['class' => 'form-select']])
            ->add('mailerUseSmtp', CheckboxType::class, ['label' => 'administration.settings.mailSettings.form.useSmtp', 'required' => false])
            ->add('mailerHost', TextType::class, ['label' => 'administration.settings.mailSettings.form.host', 'attr' => ['disabled' => true, 'class' => 'mail-option']])
            ->add('mailerPort', TextType::class, ['label' => 'administration.settings.mailSettings.form.port', 'attr' => ['disabled' => true, 'class' => 'mail-option']])
            ->add('mailerUser', TextType::class, ['label' => 'administration.settings.mailSettings.form.user', 'required' => false, 'attr' => ['disabled' => true, 'class' => 'mail-option']])
            ->add('mailerPassword', PasswordType::class, ['label' => 'administration.settings.mailSettings.form.password', 'help' => 'administration.settings.mailSettings.help.password', 'required' => false, 'attr' => ['disabled' => true, 'class' => 'mail-option']])
            ->add('mailerFromAddress', EmailType::class, ['label' => 'administration.settings.mailSettings.form.fromAddress', 'required' => false])
            ->add('mailerFromName', TextType::class, ['label' => 'administration.settings.mailSettings.form.fromName', 'required' => false])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $configValues = [
                'mosparo_name' => $form->get('mosparoName')->getData(),

                'default_locale' => $form->get('defaultLocale')->getData(),
                'default_date_format' => $form->get('defaultDateFormat')->getData(),
                'default_time_format' => $form->get('defaultTimeFormat')->getData(),
                'default_timezone' => $form->get('defaultTimezone')->getData(),

                'mailer_transport' => !$form->get('mailerUseSmtp')->isEmpty() ? 'smtp' : '',
                'mailer_from_address' => $form->get('mailerFromAddress')->getData(),
                'mailer_from_name' => $form->get('mailerFromName')->getData(),
            ];

            if ($configValues['mailer_transport'] === 'smtp') {
                $configValues = array_merge($configValues, [
                    'mailer_host' => $form->get('mailerHost')->getData(),
                    'mailer_port' => $form->get('mailerPort')->getData(),
                    'mailer_user' => $form->get('mailerUser')->getData(),
                ]);

                if (!$form->get('mailerPassword')->isEmpty()) {
                    $configValues['mailer_password'] = $form->get('mailerPassword')->getData();
                } else if ($form->get('mailerUser')->isEmpty()) {
                    $configValues['mailer_password'] = '';
                }
            }

            $configValues['mailer_dsn'] = $this->configHelper->buildMailerDsn($configValues);

            $this->configHelper->writeEnvironmentConfig($configValues);

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'administration.settings.message.savedSuccessfully',
                    [],
                    'mosparo'
                )
            );

            return $this->redirectToRoute('administration_settings');
        }

        return $this->render('administration/settings/settings.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
