<?php

namespace Mosparo\Controller\Administration;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\ProjectMember;
use Mosparo\Entity\User;
use Mosparo\Enum\AuthSource;
use Mosparo\Form\PasswordFormType;
use Mosparo\Helper\ConfigHelper;
use Mosparo\Helper\PasswordHelper;
use Mosparo\Repository\WebauthnCredentialRepository;
use Mosparo\Security\Oidc\OidcManager;
use Mosparo\UserInterface\GridTable\Adapter\OrmAdapter;
use Mosparo\UserInterface\GridTable\Column;
use Mosparo\UserInterface\GridTable\Factory;
use Mosparo\Util\TokenGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;

#[Route('/administration/users')]
class UserController extends AbstractController
{
    protected EntityManagerInterface $entityManager;

    protected UserPasswordHasherInterface $userPasswordHasher;

    protected TokenGenerator $tokenGenerator;

    protected ConfigHelper $configHelper;

    protected PasswordHelper $passwordHelper;

    protected OidcManager $oidcManager;

    protected WebauthnCredentialRepository $webauthnCredentialRepository;

    protected TranslatorInterface $translator;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher,
        TokenGenerator $tokenGenerator,
        ConfigHelper $configHelper,
        PasswordHelper $passwordHelper,
        OidcManager $oidcManager,
        WebauthnCredentialRepository $webauthnCredentialRepository,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->userPasswordHasher = $userPasswordHasher;
        $this->tokenGenerator = $tokenGenerator;
        $this->configHelper = $configHelper;
        $this->passwordHelper = $passwordHelper;
        $this->oidcManager = $oidcManager;
        $this->webauthnCredentialRepository = $webauthnCredentialRepository;
        $this->translator = $translator;
    }

    #[Route('/', name: 'administration_user_list')]
    public function index(Factory $factory): Response
    {
        $adapter = (new OrmAdapter($this->entityManager, User::class));

        $table = $factory->create($adapter)
            ->addColumn(new Column(
                'email',
                'administration.user.list.user',
            ))
            ->addColumn(new Column(
                'roles',
                'administration.user.list.roles',
                template: 'administration/user/list/_roles.html.twig',
            ))
            ->addColumn(new Column(
                'auth',
                'administration.user.list.auth',
                mapped: false,
                sortable: false,
                template: 'administration/user/list/_auth.html.twig',
            ))
            ->addColumn(new Column(
                'actions',
                'administration.user.list.actions',
                sortable: false,
                mapped: false,
                template: 'administration/user/list/_actions.html.twig',
                cellClass: 'collapsed-label-invisible action-buttons',
            ))
            ->setSortBy('email')
        ;

        $table->query();

        return $this->render('administration/user/list.html.twig', [
            'table' => $table
        ]);
    }

    #[Route('/add/choose-auth-source', name: 'administration_user_add_choose_auth_source')]
    public function add(): Response
    {
        $options = [];

        if ($this->isLocalAuthAllowed()) {
            $options[] = AuthSource::LOCAL;
        }

        if ($this->oidcManager->hasEnabledClients()) {
            $options[] = AuthSource::REMOTE_OIDC;
        }

        return $this->render('administration/user/add_choose_auth_source.html.twig', [
            'options' => $options,
        ]);
    }

    #[Route('/{id}/auth-method', name: 'administration_user_auth_method')]
    public function modifyAuthMethod(Request $request, User $user): Response
    {
        $options = [];
        if ($user->getAuthSource() === AuthSource::LOCAL) {
            if ($user->getAuthMethod() === 'password') {
                $options['administration.user.authMethod.option.resetPassword'] = 'password_reset';

                if ($user->isGoogleAuthenticatorEnabled()) {
                    $options['administration.user.authMethod.option.reset2fa'] = '2fa_reset';
                }
            } else if ($user->getAuthMethod() === 'webauthn') {
                $options['administration.user.authMethod.option.resetWebauthn'] = 'webauthn_reset';
            }

            if ($this->oidcManager->hasEnabledClients()) {
                $options['administration.user.authMethod.option.switchToOidc'] = 'switch_to_oidc';
            }
        } else if ($user->getAuthSource() === AuthSource::REMOTE_OIDC) {
            if ($this->oidcManager->hasEnabledClients() && count($this->oidcManager->getEnabledClients()) > 1) {
                $options['administration.user.authMethod.option.changeOidcClient'] = 'change_auth_method';
            }

            if ($this->isLocalAuthAllowed()) {
                $options['administration.user.authMethod.option.switchToLocal'] = 'switch_to_local';
            }
        }

        $isNewPassword = !($user->getAuthMethod() === 'password');

        $builder = $this->createFormBuilder($user, ['translation_domain' => 'mosparo'])
            ->add('action', ChoiceType::class, [
                'label' => 'administration.user.form.authMethod',
                'required' => true,
                'expanded' => true,
                'mapped' => false,
                'choices' => $options,
                'constraints' => [
                    new NotBlank(message: 'authMethod.form.constraint.chooseAction'),
                ],
            ])
        ;

        $this->addPasswordFields($builder, $isNewPassword);
        $this->addOidcClientField($builder, $user, false, false);

        $form = $builder->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $action = $form->get('action')->getData();
            $sendPasswordResetEmail = false;
            $successMessage = 'administration.user.authMethod.message.successfullySaved';

            if (in_array($action, ['password_reset', 'webauthn_reset', 'switch_to_local'])) {
                if ($user->getAuthSource() !== AuthSource::LOCAL) {
                    $user->setAuthSource(AuthSource::LOCAL);
                }

                if ($user->getAuthMethod() !== 'password') {
                    $user->setAuthMethod('password');
                }

                $sendPasswordResetEmail = $this->setPassword($user, $form, false);
                $successMessage = $sendPasswordResetEmail
                    ? 'administration.user.authMethod.message.successfullySentPasswordResetEmail'
                    : 'administration.user.authMethod.message.successfullyPasswordReset'
                ;

                $this->resetWebauthnKeys($user);
                $user->setUserHandle(null);

                $successMessage = match($action) {
                    'password_reset' => $successMessage,
                    'webauthn_reset' => 'administration.user.authMethod.message.successfullyWebauthnReset',
                    'switch_to_local' => 'administration.user.authMethod.message.successfullySwitchedToLocal',
                };
            } else if ($action === '2fa_reset') {
                $user->setGoogleAuthenticatorSecret(null);
                $user->resetBackupCodes();

                $successMessage = 'administration.user.authMethod.message.successfully2faReset';
            } else if (in_array($action, ['change_auth_method', 'switch_to_oidc'])) {
                if ($user->getAuthSource() !== AuthSource::REMOTE_OIDC) {
                    $user->setAuthSource(AuthSource::REMOTE_OIDC);
                }

                $authMethod = $form->get('authMethod')->getData();
                if ($user->getAuthMethod() !== $authMethod) {
                    $user->setAuthMethod($authMethod);
                }

                $user->setPassword(null);
                $user->setGoogleAuthenticatorSecret(null);
                $user->resetBackupCodes();

                $this->resetWebauthnKeys($user);
                $user->setUserHandle(null);

                $successMessage = match($action) {
                    'change_auth_method' => 'administration.user.authMethod.message.successfullyAuthMethodChanged',
                    'switch_to_oidc' => 'administration.user.authMethod.message.successfullySwitchedToOidc',
                };
            }

            $this->entityManager->flush();

            // Send reset email
            if ($sendPasswordResetEmail) {
                $response = $this->sendPasswordResetEmail($user);

                if ($response) {
                    return $response;
                }
            }

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    $successMessage,
                    [],
                    'mosparo'
                )
            );

            return $this->redirectToRoute('administration_user_list');
        }

        return $this->render('administration/user/auth_method.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'isNewPassword' => $isNewPassword,
        ]);
    }

    #[Route('/add/auth-source/{authSource}', name: 'administration_user_add_with_source', requirements: ['authSource' => '\d+'])]
    #[Route('/{id}/edit', name: 'administration_user_edit')]
    public function modifyUser(Request $request, EntityManagerInterface $entityManager, TokenGenerator $tokenGenerator, PasswordHelper $passwordHelper, ?User $user = null, ?int $authSource = null): Response
    {
        $isNewUser = false;
        if ($user === null) {
            $authSource = AuthSource::tryFrom($authSource);
            if (!$authSource) {
                throw $this->createNotFoundException();
            }

            $user = (new User())
                ->setAuthSource($authSource)
            ;

            if ($user->getAuthSource() === AuthSource::LOCAL && !$this->isLocalAuthAllowed()) {
                return $this->redirectToRoute('administration_user_add_choose_auth_source');
            }

            if ($user->getAuthSource() === AuthSource::LOCAL) {
                $user->setAuthMethod('password');
            }
            $isNewUser = true;
        }

        $isActiveUserAttributes = [];
        if ($user->hasRole('ROLE_USER') || $isNewUser) {
            $isActiveUserAttributes['checked'] = 'checked';
        }

        $isAdminUserAttributes = [];
        if ($user->hasRole('ROLE_ADMIN')) {
            $isAdminUserAttributes['checked'] = 'checked';
        }

        if (!$isNewUser && $user->getId() === $this->getUser()->getId()) {
            $isActiveUserAttributes['disabled'] = 'disabled';
            $isAdminUserAttributes['disabled'] = 'disabled';
        }

        $canCreateProjectsAttributes = [];
        if ($user->hasRole('ROLE_CAN_CREATE_PROJECTS')) {
            $canCreateProjectsAttributes['checked'] = 'checked';
        }

        $builder = $this->createFormBuilder($user, ['translation_domain' => 'mosparo'])
            ->add('email', EmailType::class, ['label' => 'administration.user.form.email'])
            ->add('isActiveUser', CheckboxType::class, [
                'label' => 'administration.user.form.isActiveUser',
                'mapped' => false,
                'required' => false,
                'attr' => $isActiveUserAttributes,
            ])
            ->add('isAdminUser', CheckboxType::class, [
                'label' => 'administration.user.form.isAdministrator',
                'mapped' => false,
                'required' => false,
                'attr' => $isAdminUserAttributes,
            ])
            ->add('canCreateProjects', CheckboxType::class, [
                'label' => 'administration.user.form.canCreateProjects',
                'help' => 'administration.user.help.canCreateProjects',
                'mapped' => false,
                'required' => false,
                'attr' => $canCreateProjectsAttributes,
            ])
        ;

        if ($user->getAuthSource() === AuthSource::LOCAL && $user->getAuthMethod() === 'password' && $isNewUser) {
            $this->addPasswordFields($builder, $isNewUser);
        } else if ($user->getAuthSource() === AuthSource::REMOTE_OIDC) {
            $this->addOidcClientField($builder, $user, $isNewUser, !$isNewUser);
        }

        $form = $builder->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $sendPasswordResetEmail = false;

            // The password fields exist only for new local users with password authentication
            if ($form->has('password')) {
                $sendPasswordResetEmail = $this->setPassword($user, $form, $isNewUser);
            }

            if ($isNewUser || $user->getId() !== $this->getUser()->getId()) {
                $isActiveUser = false;
                if ($form->get('isActiveUser')->getData()) {
                    $user->addRole('ROLE_USER');
                    $isActiveUser = true;
                } else {
                    $user->removeRole('ROLE_USER');
                }

                if ($form->get('isAdminUser')->getData() && $isActiveUser) {
                    $user->addRole('ROLE_ADMIN');
                } else {
                    $user->removeRole('ROLE_ADMIN');
                }
            }

            if ($form->get('canCreateProjects')->getData()) {
                $user->addRole('ROLE_CAN_CREATE_PROJECTS');
            } else {
                $user->removeRole('ROLE_CAN_CREATE_PROJECTS');
            }

            if ($isNewUser) {
                $this->entityManager->persist($user);
            }

            $this->entityManager->flush();

            // Send reset email
            if ($sendPasswordResetEmail) {
                $response = $this->sendPasswordResetEmail($user);

                if ($response) {
                    return $response;
                }
            }

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'administration.user.form.message.successfullySaved',
                    [],
                    'mosparo'
                )
            );

            return $this->redirectToRoute('administration_user_list');
        }

        return $this->render('administration/user/form.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'isNewUser' => $isNewUser,
        ]);
    }

    #[Route('/{id}/delete', name: 'administration_user_delete')]
    public function delete(Request $request, User $user): Response
    {
        if ($user->getId() === $this->getUser()->getId()) {
            $session = $request->getSession();
            $session->getFlashBag()->add('error', 'administration.user.delete.message.errorDeleteNotAllowed');

            return $this->redirectToRoute('administration_user_list');
        }

        $isOwnerInProject = false;
        foreach ($user->getProjectMemberships() as $membership) {
            if ($membership->getRole() === ProjectMember::ROLE_OWNER) {
                $isOwnerInProject = true;
                break;
            }
        }

        if ($isOwnerInProject) {
            $session = $request->getSession();
            $session->getFlashBag()->add('error', 'administration.user.delete.message.errorUserIsOwner');

            return $this->redirectToRoute('administration_user_list');
        }

        if ($request->request->has('delete-token')) {
            $submittedToken = $request->request->get('delete-token');

            if ($this->isCsrfTokenValid('delete-user', $submittedToken)) {
                // Remove all project memberships
                foreach ($user->getProjectMemberships() as $projectMembership) {
                    $this->entityManager->remove($projectMembership);
                }

                $this->resetWebauthnKeys($user);

                $this->entityManager->remove($user);
                $this->entityManager->flush();

                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'success',
                    $this->translator->trans(
                        'administration.user.delete.message.successfullyDeleted',
                        ['%email%' => $user->getEmail()],
                        'mosparo'
                    )
                );

                return $this->redirectToRoute('administration_user_list');
            }
        }

        return $this->render('administration/user/delete.html.twig', [
            'user' => $user,
        ]);
    }

    public function validatePasswordField($userData, ExecutionContextInterface $context)
    {
        $form = $context->getRoot();

        // In the auth method form, the password is only relevant for the password actions
        if ($form->has('action') && !in_array($form->get('action')->getData(), ['password_reset', 'webauthn_reset', 'switch_to_local'], true)) {
            return;
        }

        // If we send an email, this constraint is not relevant
        $sendPasswordResetEmail = ($form->get('sendPasswordResetEmail')->getData());
        if (!$sendPasswordResetEmail) {
            // If the password field is empty, we cannot save the user, so we have to show the violation
            if (empty($form->get('password')->get('plainPassword')->getData())) {
                $context
                    ->buildViolation('password.form.constraint.notBlank')
                    ->atPath('children[password][plainPassword][first].data')
                    ->addViolation();
            }
        }
    }

    protected function addPasswordFields(FormBuilderInterface $builder, $isNewPassword): void
    {
        $builder
            ->add('password', PasswordFormType::class, [
                'label' => 'administration.user.form.password',
                'mapped' => false,
                'required' => false,
                'is_new_user' => false,
                'is_new_password' => (!$isNewPassword),
                'constraints' => [
                    new Callback([$this, 'validatePasswordField'])
                ]
            ])
            ->add('sendPasswordResetEmail', CheckboxType::class, [
                'label' => 'administration.user.form.sendPasswordResetEmail',
                'mapped' => false,
                'required' => false,
                'data' => $isNewPassword,
            ])
        ;
    }

    public function validateAuthMethodField($userData, ExecutionContextInterface $context)
    {
        $form = $context->getRoot();

        // In the auth method form, the auth method field is only relevant for the OIDC methods
        if ($form->has('action') && !in_array($form->get('action')->getData(), ['change_auth_method', 'switch_to_oidc'], true)) {
            return;
        }

        if (empty($form->get('authMethod')->getData())) {
            $context
                ->buildViolation('authMethod.form.constraint.notBlank')
                ->addViolation();
        }
    }

    protected function addOidcClientField(FormBuilderInterface $builder, User $user, bool $isMapped, bool $isDisabled): void
    {
        $oidcClients = [];
        foreach ($this->oidcManager->getClients() as $client) {
            $oidcClients[$client->getName()] = sprintf('oidc_%s', $client->getKey());
        }
        $builder
            ->add('authMethod', ChoiceType::class, [
                'label' => 'administration.user.form.oidcClient',
                'placeholder' => 'form.pleaseChoose',
                'choices' => $oidcClients,
                'data' => $user->getAuthMethod(),
                'mapped' => $isMapped,
                'disabled' => $isDisabled,
                'constraints' => (!$isDisabled) ? [
                    new Callback([$this, 'validateAuthMethodField'])
                ] : [],
            ])
        ;
    }

    protected function setPassword(User $user, FormInterface $form, bool $isNewUser): bool
    {
        $sendPasswordResetEmail = ($form->get('sendPasswordResetEmail')->getData());
        if ($isNewUser && $sendPasswordResetEmail) {
            // Set a random password for a new user when we send a reset email.
            $user->setPassword($this->userPasswordHasher->hashPassword(
                $user,
                $this->tokenGenerator->generateToken()
            ));
        } else {
            $passwordField = $form->get('password');
            if ($isNewUser || !empty($passwordField->get('plainPassword')->getData())) {
                $user->setPassword($this->userPasswordHasher->hashPassword(
                    $user,
                    $passwordField->get('plainPassword')->getData()
                ));
            }
        }

        if ($sendPasswordResetEmail) {
            return true;
        }

        return false;
    }

    protected function sendPasswordResetEmail(User $user): ?Response
    {
        try {
            $this->passwordHelper->sendResetPasswordEmail($user, true);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('error', $this->translator->trans(
                'administration.user.form.message.errorCreatingResetToken',
                ['%errorMessage%' => $e->getMessage()],
                'mosparo'
            ));

            return $this->redirectToRoute('administration_user_list');
        } catch (TransportException $e) {
            $this->addFlash('error', $this->translator->trans(
                'administration.user.form.message.errorSendingResetEmail',
                ['%errorMessage%' => $e->getMessage()],
                'mosparo'
            ));

            return $this->redirectToRoute('administration_user_list');
        }

        return null;
    }

    protected function resetWebauthnKeys(User $user): void
    {
        if (!$user->getUserHandle()) {
            return;
        }

        $credentials = $this->webauthnCredentialRepository->findBy(['userHandle' => $user->getUserHandle()]);

        foreach ($credentials as $credential) {
            $this->entityManager->remove($credential);
        }
    }

    protected function isLocalAuthAllowed(): bool
    {
        $environmentConfig = $this->configHelper->readEnvironmentConfig();
        return !($environmentConfig['authentication_disable_local_auth_source'] ?? false);
    }
}
