<?php

namespace Mosparo\Controller\Administration;

use Mosparo\Entity\Project;
use Mosparo\Entity\ProjectMember;
use Mosparo\Entity\User;
use Mosparo\Form\PasswordFormType;
use Mosparo\Form\RuleAddMultipleItemsType;
use Mosparo\Form\RuleFormType;
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
 * @Route("/administration/users")
 */
class UserController extends AbstractController
{
    protected $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @Route("/", name="administration_user_list")
     */
    public function index(Request $request, RuleRepository $ruleRepository, DataTableFactory $dataTableFactory): Response
    {
        $table = $dataTableFactory->create(['autoWidth' => true])
            ->add('email', TextColumn::class, ['label' => 'User'])
            ->add('roles', TwigColumn::class, [
                'label' => 'Roles',
                'template' => 'administration/user/list/_roles.html.twig'
            ])
            ->add('actions', TwigColumn::class, [
                'label' => 'Actions',
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

    /**
     * @Route("/add", name="administration_user_add")
     * @Route("/{id}/edit", name="administration_user_edit")
     */
    public function modifyUser(Request $request, User $user = null): Response
    {
        $isNewUser = false;
        if ($user === null) {
            $user = new User();
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

        $form = $this->createFormBuilder($user)
            ->add('email', EmailType::class)
            ->add('password', PasswordFormType::class, [
                'mapped' => false,
                'required' => $isNewUser,
                'is_new_password' => (!$isNewUser),
                'help' => 'Leave these fields empty if no password change is required.'
            ])
            ->add('isActiveUser', CheckboxType::class, [
                'label' => 'Is active user',
                'mapped' => false,
                'required' => false,
                'attr' => $isActiveUserAttributes
            ])
            ->add('isAdminUser', CheckboxType::class, [
                'label' => 'Is administrator',
                'mapped' => false,
                'required' => false,
                'attr' => $isAdminUserAttributes
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $passwordField = $form->get('password');
            if ($isNewUser || !empty($passwordField->get('plainPassword')->getData())) {
                $user->setPassword($this->passwordEncoder->encodePassword(
                    $user,
                    $passwordField->get('plainPassword')->getData()
                ));
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

            if ($isNewUser) {
                $entityManager->persist($user);
            }

            $entityManager->flush();

            // RuleSet the flash message
            $session = $request->getSession();
            $session->getFlashBag()->add('success', 'The user was successfully saved.');

            return $this->redirectToRoute('administration_user_list');
        }

        return $this->render('administration/user/form.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'isNewUser' => $isNewUser,
        ]);
    }

    /**
     * @Route("/{id}/delete", name="administration_user_delete")
     */
    public function delete(Request $request, User $user): Response
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
            $session->getFlashBag()->add('error', 'The user is an owner of at least one project. You cannot delete owner of projects.');

            return $this->redirectToRoute('administration_user_list');
        }

        if ($request->request->has('delete-token')) {
            $submittedToken = $request->request->get('delete-token');

            if ($this->isCsrfTokenValid('delete-user', $submittedToken)) {
                $entityManager = $this->getDoctrine()->getManager();

                $entityManager->remove($user);
                $entityManager->flush();

                $session = $request->getSession();
                $session->getFlashBag()->add('error', 'The user ' . $user->getEmail() . ' was deleted successfully.');

                return $this->redirectToRoute('administration_user_list');
            }
        }

        return $this->render('administration/user/delete.html.twig', [
            'user' => $user,
        ]);
    }
}
