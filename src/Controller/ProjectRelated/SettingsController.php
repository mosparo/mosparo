<?php

namespace Mosparo\Controller\ProjectRelated;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Mosparo\Entity\ProjectMember;
use Mosparo\Entity\User;
use Mosparo\Form\DesignSettingsFormType;
use Mosparo\Form\ExtendedProjectFormType;
use Mosparo\Helper\DesignHelper;
use Mosparo\Util\TokenGenerator;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/settings")
 */
class SettingsController extends AbstractController implements ProjectRelatedInterface
{
    use ProjectRelatedTrait;

    protected TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @Route("/general", name="settings_general")
     */
    public function general(Request $request, EntityManagerInterface $entityManager): Response
    {
        $project = $this->getActiveProject();

        $form = $this->createForm(ExtendedProjectFormType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'settings.general.message.successfullySaved',
                    [],
                    'mosparo'
                )
            );

            return $this->redirectToRoute('settings_general');
        }

        return $this->render('project_related/settings/general.html.twig', [
            'form' => $form->createView(),
            'project' => $project,
        ]);
    }

    /**
     * @Route("/members", name="settings_member_list")
     */
    public function memberList(Request $request, DataTableFactory $dataTableFactory): Response
    {
        $project = $this->getActiveProject();

        $table = $dataTableFactory->create(['autoWidth' => true])
            ->add('user', TextColumn::class, ['label' => 'settings.projectMember.list.user', 'propertyPath' => 'user.email'])
            ->add('role', TwigColumn::class, [
                'label' => 'settings.projectMember.list.role',
                'template' => 'project_related/settings/member/list/_role.html.twig'
            ])
            ->add('actions', TwigColumn::class, [
                'label' => 'settings.projectMember.list.actions',
                'className' => 'buttons',
                'template' => 'project_related/settings/member/list/_actions.html.twig'
            ])
            ->createAdapter(ORMAdapter::class, [
                'entity' => ProjectMember::class,
                'query' => function (QueryBuilder $builder) use ($project) {
                    $builder
                        ->select('e')
                        ->from(ProjectMember::class, 'e')
                        ->where('e.project = :project')
                        ->setParameter(':project', $project);
                },
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('project_related/settings/member/list.html.twig', [
            'project' => $project,
            'datatable' => $table
        ]);
    }

    /**
     * @Route("/members/add", name="settings_member_add")
     * @Route("/members/{id}/edit", name="settings_member_edit")
     */
    public function memberModify(Request $request, EntityManagerInterface $entityManager, ProjectMember $projectMember = null): Response
    {
        $isNew = false;
        $isOwner = false;
        $emailAddress = '';
        $emailFieldAttributes = [];
        if ($projectMember === null) {
            $projectMember = new ProjectMember();
            $projectMember->setProject($this->getActiveProject());
            $isNew = true;
        } else {
            $isOwner = ($projectMember->getRole() === ProjectMember::ROLE_OWNER);
            $emailAddress = $projectMember->getUser()->getEmail();
            $emailFieldAttributes = ['readonly' => true];
        }

        $projectMemberRoles = [
            'project.roles.reader' => ProjectMember::ROLE_READER,
            'project.roles.editor' => ProjectMember::ROLE_EDITOR,
            'project.roles.owner' => ProjectMember::ROLE_OWNER
        ];

        $form = $this->createFormBuilder($projectMember, ['translation_domain' => 'mosparo'])
            ->add('email', EmailType::class, ['label' => 'settings.projectMember.form.email', 'mapped' => false, 'data' => $emailAddress, 'attr' => $emailFieldAttributes])
            ->add('role', ChoiceType::class, ['label' => 'settings.projectMember.form.role', 'choices' => $projectMemberRoles, 'attr' => ['class' => 'form-select']])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository = $entityManager->getRepository(User::class);

            if ($isNew) {
                $user = $userRepository->findOneBy(['email' => $form->get('email')->getData()]);
                if ($user === null) {
                    $session = $request->getSession();
                    $session->getFlashBag()->add(
                        'error',
                        $this->translator->trans(
                            'settings.projectMember.form.message.errorUserNotFound',
                            [],
                            'mosparo'
                        )
                    );

                    return $this->redirectToRoute('settings_member_list');
                }

                $projectMember->setUser($user);
                $entityManager->persist($projectMember);
            } else if ($isOwner) {
                $numberOfOwner = 0;
                foreach ($this->getActiveProject()->getProjectMembers() as $member) {
                    if ($member->getRole() === ProjectMember::ROLE_OWNER) {
                        $numberOfOwner++;
                    }
                }

                if ($numberOfOwner === 0) {
                    $session = $request->getSession();
                    $session->getFlashBag()->add(
                        'error',
                        $this->translator->trans(
                            'settings.projectMember.form.message.errorNeedsOwner',
                            [],
                            'mosparo'
                        )
                    );

                    return $this->redirectToRoute('settings_member_list');
                }
            }

            $entityManager->flush();

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'settings.projectMember.form.message.successfullySaved',
                    [],
                    'mosparo'
                )
            );

            return $this->redirectToRoute('settings_member_list');
        }

        return $this->render('project_related/settings/member/form.html.twig', [
            'projectMember' => $projectMember,
            'form' => $form->createView(),
            'isNew' => $isNew,
        ]);
    }

    /**
     * @Route("/members/{id}/remove", name="settings_member_remove")
     */
    public function memberRemove(Request $request, EntityManagerInterface $entityManager, ProjectMember $projectMember): Response
    {
        if ($projectMember->getRole() === ProjectMember::ROLE_OWNER) {
            $numberOfOwner = 0;
            foreach ($this->getActiveProject()->getProjectMembers() as $member) {
                if ($member->getRole() === ProjectMember::ROLE_OWNER) {
                    $numberOfOwner++;
                }
            }

            if ($numberOfOwner <= 1) {
                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'error',
                    $this->translator->trans(
                        'settings.projectMember.form.message.errorNeedsOwner',
                        [],
                        'mosparo'
                    )
                );

                return $this->redirectToRoute('settings_member_list');
            }
        }

        if ($request->request->has('delete-token')) {
            $submittedToken = $request->request->get('delete-token');

            if ($this->isCsrfTokenValid('delete-project-member', $submittedToken)) {
                $entityManager->remove($projectMember);
                $entityManager->flush();

                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'error',
                    $this->translator->trans(
                        'settings.projectMember.remove.message.successfullyRemoved',
                        ['%projectMemberName%' => $projectMember->getUser()->getEmail()],
                        'mosparo'
                    )
                );

                return $this->redirectToRoute('settings_member_list');
            }
        }

        return $this->render('project_related/settings/member/remove.html.twig', [
            'projectMember' => $projectMember,
        ]);
    }

    /**
     * @Route("/security", name="settings_security")
     */
    public function security(Request $request, EntityManagerInterface $entityManager): Response
    {
        $project = $this->getActiveProject();
        $config = $project->getConfigValues();

        $form = $this->createFormBuilder($config, ['translation_domain' => 'mosparo'])
            // Minimum time
            ->add('minimumTimeActive', CheckboxType::class, ['label' => 'settings.security.form.minimumTimeActive', 'required' => false, 'attr' => ['class' => 'card-field-switch']])
            ->add('minimumTimeSeconds', NumberType::class, ['label' => 'settings.security.form.minimumTimeSeconds', 'help' => 'settings.security.form.minimumTimeSecondsHelp', 'required' => false])

            // Honeypot
            ->add('honeypotFieldActive', CheckboxType::class, ['label' => 'settings.security.form.honeypotFieldActive', 'required' => false, 'attr' => ['class' => 'card-field-switch']])
            ->add('honeypotFieldName', TextType::class, ['label' => 'settings.security.form.honeypotFieldName', 'help' => 'settings.security.form.honeypotFieldNameHelp', 'required' => false, 'constraints' => [
                new Regex([
                    'pattern' => '/^[a-z0-9\-]*$/i',
                    'message' => $this->translator->trans(
                        'settings.security.validation.honeypotFieldNameInvalidCharacter',
                        [],
                        'mosparo'
                    ),
                ])
            ]])

            // delay
            ->add('delayActive', CheckboxType::class, ['label' => 'settings.security.form.delayActive', 'required' => false, 'attr' => ['class' => 'card-field-switch']])
            ->add('delayNumberOfRequests', NumberType::class, ['label' => 'settings.security.form.delayNumberOfAllowedRequests', 'help' => 'settings.security.form.delayNumberOfAllowedRequestsHelp'])
            ->add('delayDetectionTimeFrame', NumberType::class, ['label' => 'settings.security.form.delayDetectionTimeFrame', 'help' => 'settings.security.form.delayDetectionTimeFrameHelp'])
            ->add('delayTime', NumberType::class, ['label' => 'settings.security.form.delayTime', 'help' => 'settings.security.form.delayTimeHelp'])
            ->add('delayMultiplicator', NumberType::class, ['label' => 'settings.security.form.delayMultiplicator', 'help' => 'settings.security.form.delayMultiplicatorHelp'])

            // lockout
            ->add('lockoutActive', CheckboxType::class, ['label' => 'settings.security.form.lockoutActive', 'required' => false, 'attr' => ['class' => 'card-field-switch']])
            ->add('lockoutNumberOfRequests', NumberType::class, ['label' => 'settings.security.form.lockoutNumberOfAllowedRequests', 'help' => 'settings.security.form.lockoutNumberOfAllowedRequestsHelp'])
            ->add('lockoutDetectionTimeFrame', NumberType::class, ['label' => 'settings.security.form.lockoutDetectionTimeFrame', 'help' => 'settings.security.form.lockoutDetectionTimeFrameHelp'])
            ->add('lockoutTime', NumberType::class, ['label' => 'settings.security.form.lockoutTime', 'help' => 'settings.security.form.lockoutTimeHelp'])
            ->add('lockoutMultiplicator', NumberType::class, ['label' => 'settings.security.form.lockoutMultiplicator', 'help' => 'settings.security.form.lockoutMultiplicatorHelp'])

            ->add('ipAllowList', TextareaType::class, ['label' => 'settings.security.form.ipAllowList', 'required' => false, 'help' => 'settings.security.form.ipAllowListHelp'])

            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            foreach ($data as $key => $value) {
                $project->setConfigValue($key, $value);
            }

            $entityManager->flush();

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'settings.security.message.successfullySaved',
                    [],
                    'mosparo'
                )
            );

            return $this->redirectToRoute('settings_security');
        }

        return $this->render('project_related/settings/security.html.twig', [
            'form' => $form->createView(),
            'project' => $project,
        ]);
    }

    /**
     * @Route("/design", name="settings_design")
     */
    public function design(Request $request, EntityManagerInterface $entityManager, DesignHelper $designHelper): Response
    {
        $project = $this->getActiveProject();
        $config = $project->getConfigValues();

        $form = $this->createForm(DesignSettingsFormType::class, $config);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            foreach ($data as $key => $value) {
                if ($value === null) {
                    $value = '';
                }

                $project->setConfigValue($key, $value);
            }

            // Prepare the css cache
            $designHelper->generateCssCache($project);

            $entityManager->flush();

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'settings.design.message.successfullySaved',
                    [],
                    'mosparo'
                )
            );

            return $this->redirectToRoute('settings_design');
        }

        return $this->render('project_related/settings/design.html.twig', [
            'form' => $form->createView(),
            'project' => $project,
            'sizeVariables' => $designHelper->getBoxSizeVariables(),
            'maxRadiusForLogo' => $designHelper->getMaxRadiusForLogo(),
        ]);
    }

    /**
     * @Route("/reissue-keys", name="settings_reissue_keys")
     */
    public function reissueKeys(Request $request, EntityManagerInterface $entityManager): Response
    {
        $activeProject = $this->projectHelper->getActiveProject();

        if (!$this->isGranted('ROLE_ADMIN') && !$activeProject->isProjectOwner($this->getUser())) {
            $session = $request->getSession();
            $session->getFlashBag()->add(
                'warning',
                $this->translator->trans(
                    'settings.general.apiKeys.reissueApiKeys.message.errorOnlyOwner',
                    [],
                    'mosparo'
                )
            );

            return $this->redirectToRoute('settings_general');
        }

        if ($request->request->has('reissue-token')) {
            $submittedToken = $request->request->get('reissue-token');

            if ($this->isCsrfTokenValid('reissue-api-keys', $submittedToken)) {
                $tokenGenerator = new TokenGenerator();
                $activeProject->setPublicKey($tokenGenerator->generateToken());
                $activeProject->setPrivateKey($tokenGenerator->generateToken());

                $entityManager->flush();

                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'warning',
                    $this->translator->trans(
                        'settings.general.apiKeys.reissueApiKeys.message.successfullyReissued',
                        [],
                        'mosparo'
                    )
                );

                return $this->redirectToRoute('settings_general');
            }
        }

        return $this->render('project_related/settings/reissue.html.twig', [
            'project' => $activeProject,
        ]);
    }
}