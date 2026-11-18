<?php

namespace Mosparo\Controller\Administration;

use Mosparo\Form\OidcClientFormType;
use Mosparo\Helper\ConfigHelper;
use Mosparo\Helper\PasswordHelper;
use Mosparo\Security\Oidc\OidcManager;
use Mosparo\UserInterface\GridTable\Adapter\ArrayAdapter;
use Mosparo\UserInterface\GridTable\Column;
use Mosparo\UserInterface\GridTable\Factory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/administration/authentication')]
class AuthenticationController extends AbstractController
{
    protected ConfigHelper $configHelper;

    protected TranslatorInterface $translator;

    protected OidcManager $oidcManager;

    public function __construct(
        ConfigHelper $configHelper,
        TranslatorInterface $translator,
        OidcManager $oidcManager
    ) {
        $this->configHelper = $configHelper;
        $this->translator = $translator;
        $this->oidcManager = $oidcManager;
    }

    #[Route('/', name: 'administration_authentication')]
    public function authentication(Factory $factory): Response
    {
        $adapter = (new ArrayAdapter($this->oidcManager->getClients()));

        $table = $factory->create($adapter)
            ->setPageSizes([10])
            ->setPerPage(10) // We have a hard limit of 10 OIDC clients, so we do not show more in this table.
            ->addColumn(new Column(
                'name',
                'administration.authentication.oidcClient.list.name',
                template: 'administration/authentication/list/_name.html.twig',
            ))
            ->addColumn(new Column(
                'enabled',
                'administration.authentication.oidcClient.list.status',
                template: 'administration/authentication/list/_status.html.twig',
            ))
            ->addColumn(new Column(
                'users',
                'administration.authentication.oidcClient.list.users',
                isNumeric: true,
                sortable: false,
                mapped: false,
                template: 'administration/authentication/list/_users.html.twig',
            ))
            ->addColumn(new Column(
                'actions',
                'administration.authentication.oidcClient.list.actions',
                sortable: false,
                mapped: false,
                template: 'administration/authentication/list/_actions.html.twig',
                cellClass: 'collapsed-label-invisible action-buttons',
            ))
            ->setSortBy('email')
        ;

        $table->query();

        return $this->render('administration/authentication/overview.html.twig', [
            'table' => $table,
            'configuredClients' => count($this->oidcManager->getClients()),
        ]);
    }

    #[Route('/general-settings', name: 'administration_authentication_general_settings')]
    public function generalSettings(Request $request): Response
    {
        $environmentConfig = $this->configHelper->readEnvironmentConfig();
        $config = [
            'passwordMinLength' => $environmentConfig['authentication_password_min_length'] ?? PasswordHelper::DEFAULT_MIN_LENGTH,
            'passwordMinStrength' => $environmentConfig['authentication_password_min_strength'] ?? PasswordHelper::DEFAULT_MIN_STRENGTH,
            'passwordBlockCompromised' => $environmentConfig['authentication_password_block_compromised'] ?? false,
            'enforceTwoFactorAuthentication' => $environmentConfig['authentication_enforce_two_factor_authentication'] ?? false,
            'enforcePasskeyAuthentication' => $environmentConfig['authentication_enforce_passkey_authentication'] ?? false,
            'disableLocalAuthSource' => $environmentConfig['authentication_disable_local_auth_source'] ?? false,
        ];

        $formBuilder = $this->createFormBuilder($config, ['translation_domain' => 'mosparo'])
            ->add('passwordMinLength', IntegerType::class, [
                'label' => 'administration.authentication.generalSettings.form.passwordMinLength',
                'help' => 'administration.authentication.generalSettings.form.passwordMinLengthHelp',
                'attr' => ['class' => 'text-end'],
                'constraints' => [
                    new Range(min: 8, max: 128),
                ],
            ])
            ->add('passwordMinStrength', ChoiceType::class, [
                'label' => 'administration.authentication.generalSettings.form.passwordMinStrength',
                'help' => 'administration.authentication.generalSettings.form.passwordMinStrengthHelp',
                'choices' => [
                    'passwordForm.strength.none' => PasswordStrength::STRENGTH_VERY_WEAK,
                    'passwordForm.strength.weak' => PasswordStrength::STRENGTH_WEAK,
                    'passwordForm.strength.fair' => PasswordStrength::STRENGTH_MEDIUM,
                    'passwordForm.strength.good' => PasswordStrength::STRENGTH_STRONG,
                    'passwordForm.strength.strong' => PasswordStrength::STRENGTH_VERY_STRONG,
                ],
            ])
            ->add('passwordBlockCompromised', CheckboxType::class, [
                'label' => 'administration.authentication.generalSettings.form.blockCompromised',
                'help' => 'administration.authentication.generalSettings.form.blockCompromisedHelp',
                'required' => false,
            ])
            ->add('enforceTwoFactorAuthentication', CheckboxType::class, [
                'label' => 'administration.authentication.generalSettings.form.enforceTwoFactorAuthentication',
                'help' => 'administration.authentication.generalSettings.form.enforceTwoFactorAuthenticationHelp',
                'required' => false,
            ])
            ->add('disableLocalAuthSource', CheckboxType::class, [
                'label' => 'administration.authentication.generalSettings.form.disableLocalAuthSource',
                'help' => 'administration.authentication.generalSettings.form.disableLocalAuthSourceHelp',
                'required' => false,
            ])
        ;

        if ($this->configHelper->getEnvironmentConfigValue('mosparo_webauthn_rp_id') !== false) {
            $formBuilder
                ->add('enforcePasskeyAuthentication', CheckboxType::class, [
                    'label' => 'administration.authentication.generalSettings.form.enforcePasskeyAuthentication',
                    'help' => 'administration.authentication.generalSettings.form.enforcePasskeyAuthenticationHelp',
                    'required' => false,
                ])
            ;
        }

        $form = $formBuilder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $configValues = [
                'authentication_disable_local_auth_source' => $form->get('disableLocalAuthSource')->getData(),
            ];

            if (!$configValues['authentication_disable_local_auth_source']) {
                $configValues = array_merge($configValues, [
                    'authentication_enforce_passkey_authentication' => $form->has('enforcePasskeyAuthentication') ? $form->get('enforcePasskeyAuthentication')->getData() : false,
                ]);
            }

            if (!$configValues['authentication_disable_local_auth_source'] && !$configValues['authentication_enforce_passkey_authentication']) {
                $configValues = array_merge($configValues, [
                    'authentication_password_min_length' => $form->get('passwordMinLength')->getData(),
                    'authentication_password_min_strength' => $form->get('passwordMinStrength')->getData(),
                    'authentication_password_block_compromised' => $form->get('passwordBlockCompromised')->getData(),
                    'authentication_enforce_two_factor_authentication' => $form->get('enforceTwoFactorAuthentication')->getData(),
                ]);
            }

            $this->configHelper->writeEnvironmentConfig($configValues);

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'administration.authentication.generalSettings.message.savedSuccessfully',
                    [],
                    'mosparo'
                )
            );

            return $this->redirectToRoute('administration_authentication');
        }

        return $this->render('administration/authentication/general_settings.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/oidc-clients/add', name: 'administration_authentication_oidc_client_add')]
    #[Route('/oidc-clients/{key}/edit', name: 'administration_authentication_oidc_client_edit')]
    public function oidcClientForm(Request $request, ?string $key = null): Response
    {
        $isNew = false;
        if ($key === null) {
            $key = $this->oidcManager->getAvailableKey();

            // If we didn't get a key, we have used all 10 slots already.
            if ($key === null) {
                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'error',
                    $this->translator->trans(
                        'administration.authentication.oidcClient.message.maxNumberOfClients',
                        [],
                        'mosparo'
                    )
                );

                return $this->redirectToRoute('administration_authentication');
            }

            $isNew = true;
        }

        $oidcClientConfig = $this->oidcManager->getClientConfiguration($key);
        if (!$oidcClientConfig) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(OidcClientFormType::class, $oidcClientConfig, [
            'is_new_oidc_client' => $isNew,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $clientSecret = $form->get('clientSecret')->getData();
            if ($clientSecret !== null) {
                $oidcClientConfig->setClientSecret($clientSecret);
            }

            $this->oidcManager->saveClientConfiguration($oidcClientConfig);

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'administration.authentication.oidcClient.message.successfullySaved',
                    ['%name%' => $oidcClientConfig->getName()],
                    'mosparo'
                )
            );

            return $this->redirectToRoute('administration_authentication');
        }

        $hasAdditionalSettings = false;
        if (!$isNew) {
            $hasAdditionalSettings =
                $oidcClientConfig->isAssumeEmailVerified() ||
                !$oidcClientConfig->isEnableRetrieveUserInfo() ||
                !empty($oidcClientConfig->getAdditionalScopes())
            ;
        }

        return $this->render('administration/authentication/oidc_client_form.html.twig', [
            'oidcClientConfig' => $oidcClientConfig,
            'form' => $form->createView(),
            'isNew' => $isNew,
            'hasAdditionalSettings' => $hasAdditionalSettings,
        ]);
    }

    #[Route('/oidc-clients/{key}/delete', name: 'administration_authentication_oidc_client_delete')]
    public function oidcClientDelete(Request $request, string $key): Response
    {
        $oidcClientConfig = $this->oidcManager->getClientConfiguration($key);
        if (!$oidcClientConfig) {
            throw new NotFoundHttpException();
        }

        $numberOfUsers = $this->oidcManager->countUsersForOidcClient($key);
        if ($numberOfUsers > 0) {
            $session = $request->getSession();
            $session->getFlashBag()->add(
                'error',
                $this->translator->trans(
                    'administration.authentication.oidcClient.message.oidcClientHasUsers',
                    ['%name%' => $oidcClientConfig->getName()],
                    'mosparo'
                )
            );

            return $this->redirectToRoute('administration_authentication');
        }

        if ($request->request->has('delete-token')) {
            $submittedToken = $request->request->get('delete-token');

            if ($this->isCsrfTokenValid('delete-oidc-client', $submittedToken)) {
                $this->oidcManager->deleteClientConfiguration($oidcClientConfig);

                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'success',
                    $this->translator->trans(
                        'administration.authentication.oidcClient.message.successfullyDeleted',
                        ['%name%' => $oidcClientConfig->getName()],
                        'mosparo'
                    )
                );

                return $this->redirectToRoute('administration_authentication');
            }
        }

        return $this->render('administration/authentication/oidc_client_delete.html.twig', [
            'oidcClientConfig' => $oidcClientConfig,
        ]);
    }
}
