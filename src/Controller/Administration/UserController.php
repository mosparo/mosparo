<?php

namespace Mosparo\Controller\Administration;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\ProjectMember;
use Mosparo\Entity\User;
use Mosparo\Form\PasswordFormType;
use Mosparo\Helper\PasswordHelper;
use Mosparo\Util\TokenGenerator;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;

#[Route('/administration/users')]
class UserController extends AbstractController
{
    protected UserPasswordHasherInterface $userPasswordHasher;

    protected TranslatorInterface $translator;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher, TranslatorInterface $translator)
    {
        $this->userPasswordHasher = $userPasswordHasher;
        $this->translator = $translator;
    }

    #[Route('/', name: 'administration_user_list')]
    public function index(Request $request, DataTableFactory $dataTableFactory): Response
    {
        $table = $dataTableFactory->create(['autoWidth' => true])
            ->add('email', TextColumn::class, ['label' => 'administration.user.list.user'])
            ->add('roles', TwigColumn::class, [
                'label' => 'administration.user.list.roles',
                'template' => 'administration/user/list/_roles.html.twig'
            ])
            ->add('actions', TwigColumn::class, [
                'label' => 'administration.user.list.actions',
                'className' => 'buttons',
                'template' => 'administration/user/list/_actions.html.twig'
            ])
            ->createAdapter(ORMAdapter::class, [
                'entity' => User::class,
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('administration/user/list.html.twig', [
            'datatable' => $table
        ]);
    }

    #[Route('/add', name: 'administration_user_add')]
    #[Route('/{id}/edit', name: 'administration_user_edit')]
    public function modifyUser(Request $request, EntityManagerInterface $entityManager, TokenGenerator $tokenGenerator, PasswordHelper $passwordHelper, User $user = null): Response
    {
        $passwordHelp = 'administration.user.help.password';
        $isNewUser = false;
        if ($user === null) {
            $user = new User();
            $isNewUser = true;

            $passwordHelp = '';
        }

        $isActiveUserAttributes = [];
        if ($user->hasRole('ROLE_USER') || $isNewUser) {
            $isActiveUserAttributes['checked'] = 'checked';
        }

        $isAdminUserAttributes = [];
        if ($user->hasRole('ROLE_ADMIN')) {
            $isAdminUserAttributes['checked'] = 'checked';
        }

        $canCreateProjectsAttributes = [];
        if ($user->hasRole('ROLE_CAN_CREATE_PROJECTS')) {
            $canCreateProjectsAttributes['checked'] = 'checked';
        }

        $form = $this->createFormBuilder($user, ['translation_domain' => 'mosparo'])
            ->add('email', EmailType::class, ['label' => 'administration.user.form.email'])
            ->add('password', PasswordFormType::class, [
                'label' => 'administration.user.form.password',
                'mapped' => false,
                'required' => false,
                'is_new_user' => $isNewUser,
                'is_new_password' => (!$isNewUser),
                'help' => $passwordHelp,
                'constraints' => [
                    new Callback([$this, 'validatePasswordField'])
                ]
            ])
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
            ->add('sendPasswordResetEmail', CheckboxType::class, [
                'label' => 'administration.user.form.sendPasswordResetEmail',
                'mapped' => false,
                'required' => false,
                'data' => ($isNewUser),
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $sendPasswordResetEmail = ($form->get('sendPasswordResetEmail')->getData());
            if ($isNewUser && $sendPasswordResetEmail) {
                // Set a random password for the user
                $user->setPassword($this->userPasswordHasher->hashPassword(
                    $user,
                    $tokenGenerator->generateToken()
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

            if ($form->get('canCreateProjects')->getData()) {
                $user->addRole('ROLE_CAN_CREATE_PROJECTS');
            } else {
                $user->removeRole('ROLE_CAN_CREATE_PROJECTS');
            }

            if ($isNewUser) {
                $entityManager->persist($user);
            }

            $entityManager->flush();

            // Send reset email
            if ($sendPasswordResetEmail) {
                try {
                    $passwordHelper->sendResetPasswordEmail($user, true);
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
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
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
                    $entityManager->remove($projectMembership);
                }

                $entityManager->remove($user);
                $entityManager->flush();

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
        $field = $form->get('password');
        $isNewUser = $field->getConfig()->getOption('is_new_user', false);

        // This constraint is only relevant to new users
        if (!$isNewUser) {
            return;
        }

        // If we send an email, this constraint is not relevant
        $sendPasswordResetEmail = ($form->get('sendPasswordResetEmail')->getData());
        if (!$sendPasswordResetEmail) {
            $passwordField = $form->get('password');

            // If the password field is empty, we cannot save the user, so we have to show the violation
            if (empty($passwordField->get('plainPassword')->getData())) {
                $context
                    ->buildViolation('password.form.constraint.notBlank')
                    ->atPath('children[password][plainPassword][first].data')
                    ->addViolation();
            }
        }
    }
}
