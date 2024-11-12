<?php

namespace Mosparo\Controller\ProjectRelated;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Mosparo\Entity\ProjectMember;
use Mosparo\Entity\SecurityGuideline;
use Mosparo\Entity\User;
use Mosparo\Form\AdvancedProjectFormType;
use Mosparo\Form\DesignSettingsFormType;
use Mosparo\Form\ProjectFormType;
use Mosparo\Form\SecurityGuidelineFormType;
use Mosparo\Form\SecuritySettingsFormType;
use Mosparo\Helper\DesignHelper;
use Mosparo\Helper\GeoIp2Helper;
use Mosparo\Helper\ProjectGroupHelper;
use Mosparo\Util\TokenGenerator;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\NumberColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/project/{_projectId}/settings')]
class SettingsController extends AbstractController implements ProjectRelatedInterface
{
    use ProjectRelatedTrait;

    protected TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    #[Route('/general', name: 'settings_general')]
    public function general(Request $request, EntityManagerInterface $entityManager, ProjectGroupHelper $projectGroupHelper): Response
    {
        $project = $this->getActiveProject();

        $tree = $projectGroupHelper->getFullProjectGroupTreeForUser();
        $tree->sort();

        $form = $this->createForm(ProjectFormType::class, $project, [
            'tree' => $tree
        ]);
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

            return $this->redirectToRoute('settings_general', ['_projectId' => $this->getActiveProject()->getId()]);
        }

        return $this->render('project_related/settings/general.html.twig', [
            'form' => $form->createView(),
            'project' => $project,
        ]);
    }

    #[Route('/advanced', name: 'settings_advanced')]
    public function advanced(Request $request, EntityManagerInterface $entityManager): Response
    {
        $project = $this->getActiveProject();

        $form = $this->createForm(AdvancedProjectFormType::class, $project);
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

            return $this->redirectToRoute('settings_advanced', ['_projectId' => $this->getActiveProject()->getId()]);
        }

        return $this->render('project_related/settings/advanced.html.twig', [
            'form' => $form->createView(),
            'project' => $project,
        ]);
    }

    #[Route('/members', name: 'settings_member_list')]
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
                        ->setParameter('project', $project);
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

    #[Route('/members/add', name: 'settings_member_add')]
    #[Route('/members/{id}/edit', name: 'settings_member_edit')]
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

                    return $this->redirectToRoute('settings_member_list', ['_projectId' => $this->getActiveProject()->getId()]);
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

                    return $this->redirectToRoute('settings_member_list', ['_projectId' => $this->getActiveProject()->getId()]);
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

            return $this->redirectToRoute('settings_member_list', ['_projectId' => $this->getActiveProject()->getId()]);
        }

        return $this->render('project_related/settings/member/form.html.twig', [
            'projectMember' => $projectMember,
            'form' => $form->createView(),
            'isNew' => $isNew,
        ]);
    }

    #[Route('/members/{id}/remove', name: 'settings_member_remove')]
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

                return $this->redirectToRoute('settings_member_list', ['_projectId' => $this->getActiveProject()->getId()]);
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
                        'settings.projectMember.delete.message.successfullyRemoved',
                        ['%projectMemberName%' => $projectMember->getUser()->getEmail()],
                        'mosparo'
                    )
                );

                return $this->redirectToRoute('settings_member_list', ['_projectId' => $this->getActiveProject()->getId()]);
            }
        }

        return $this->render('project_related/settings/member/remove.html.twig', [
            'projectMember' => $projectMember,
        ]);
    }

    #[Route('/security', name: 'settings_security')]
    public function security(Request $request, DataTableFactory $dataTableFactory): Response
    {
        $project = $this->getActiveProject();

        $table = $dataTableFactory->create(['autoWidth' => true])
            ->add('name', TextColumn::class, ['label' => 'settings.security.guideline.list.name'])
            ->add('priority', NumberColumn::class, [
                'label' => 'settings.security.guideline.list.priority',
            ])
            ->add('actions', TwigColumn::class, [
                'label' => 'settings.security.guideline.list.actions',
                'className' => 'buttons',
                'template' => 'project_related/settings/security/list/_actions.html.twig'
            ])
            ->createAdapter(ORMAdapter::class, [
                'entity' => SecurityGuideline::class,
                'query' => function (QueryBuilder $builder) use ($project) {
                    $builder
                        ->select('e')
                        ->from(SecurityGuideline::class, 'e')
                        ->where('e.project = :project')
                        ->setParameter('project', $project);
                },
            ])
            ->addOrderBy('priority', DataTable::SORT_DESCENDING)
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('project_related/settings/security/security.html.twig', [
            'project' => $project,
            'datatable' => $table
        ]);
    }

    #[Route('/security/edit-general', name: 'settings_security_edit_general')]
    public function securityEditGeneralForm(Request $request, EntityManagerInterface $entityManager, SecurityGuideline $securityGuideline = null): Response
    {
        $project = $this->getActiveProject();
        $config = $project->getConfigValues();

        $form = $this->createForm(SecuritySettingsFormType::class, $config, ['isGeneralSettings' => true]);

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

            return $this->redirectToRoute('settings_security', ['_projectId' => $this->getActiveProject()->getId()]);
        }

        return $this->render('project_related/settings/security/general_form.html.twig', [
            'form' => $form->createView(),
            'project' => $project,
        ]);
    }

    #[Route('/security/guideline/add', name: 'settings_security_guideline_add')]
    #[Route('/security/guideline/{id}/edit', name: 'settings_security_guideline_edit')]
    public function securityGuidelineForm(Request $request, EntityManagerInterface $entityManager, GeoIp2Helper $geoIp2Helper, SecurityGuideline $securityGuideline = null): Response
    {
        $project = $this->getActiveProject();
        $isNew = false;
        if ($securityGuideline === null) {
            $securityGuideline = new SecurityGuideline();
            $securityGuideline->setProject($project);
            $isNew = true;
        }

        $geoIp2Active = $geoIp2Helper->isGeoIp2Active();
        $form = $this->createForm(SecurityGuidelineFormType::class, $securityGuideline, [
            'geoIp2Active' => $geoIp2Active,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($isNew) {
                $entityManager->persist($securityGuideline);
            }

            $entityManager->flush();

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'settings.security.message.guidelineSuccessfullySaved',
                    [],
                    'mosparo'
                )
            );

            return $this->redirectToRoute('settings_security', ['_projectId' => $this->getActiveProject()->getId()]);
        }

        return $this->render('project_related/settings/security/guideline_form.html.twig', [
            'guideline' => $securityGuideline,
            'form' => $form->createView(),
            'project' => $project,
            'isNew' => $isNew,
            'geoIp2Active' => $geoIp2Active,
        ]);
    }

    #[Route('/security/guideline/{id}/remove', name: 'settings_security_guideline_remove')]
    public function securityGuidelineRemove(Request $request, EntityManagerInterface $entityManager, SecurityGuideline $securityGuideline): Response
    {
        if ($request->request->has('delete-token')) {
            $submittedToken = $request->request->get('delete-token');

            if ($this->isCsrfTokenValid('delete-security-guideline', $submittedToken)) {
                $entityManager->remove($securityGuideline);
                $entityManager->flush();

                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'error',
                    $this->translator->trans(
                        'settings.security.guideline.delete.message.successfullyRemoved',
                        ['%guidelineName%' => $securityGuideline->getName()],
                        'mosparo'
                    )
                );

                return $this->redirectToRoute('settings_security', ['_projectId' => $this->getActiveProject()->getId()]);
            }
        }

        return $this->render('project_related/settings/security/guideline_remove.html.twig', [
            'guideline' => $securityGuideline,
        ]);
    }

    #[Route('/design', name: 'settings_design')]
    public function design(Request $request, EntityManagerInterface $entityManager, DesignHelper $designHelper): Response
    {
        $project = $this->getActiveProject();
        $config = $project->getConfigValues();

        $designMode = $project->getDesignMode();

        $form = $this->createForm(DesignSettingsFormType::class, $config, ['mode' => $designMode]);

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

            return $this->redirectToRoute('settings_design', ['_projectId' => $this->getActiveProject()->getId()]);
        }

        return $this->render('project_related/settings/design.html.twig', [
            'form' => $form->createView(),
            'project' => $project,
            'sizeVariables' => $designHelper->getBoxSizeVariables(),
            'maxRadiusForLogo' => $designHelper->getMaxRadiusForLogo(),
            'mode' => $designMode,
            'designConfigValues' => $designHelper->prepareCssVariables($project),
        ]);
    }

    #[Route('/design/switch-mode', name: 'settings_design_switch_mode')]
    public function switchDesignMode(Request $request, EntityManagerInterface $entityManager, DesignHelper $designHelper): Response
    {
        $project = $this->getActiveProject();

        if ($request->query->has('mode') && in_array($request->query->get('mode'), ['simple', 'advanced', 'invisible-simple'])) {
            $project->setConfigValue('designMode', $request->query->get('mode'));

            $entityManager->flush();
        }

        return $this->redirectToRoute('settings_design', ['_projectId' => $this->getActiveProject()->getId()]);
    }

    #[Route('/reissue-keys', name: 'settings_reissue_keys')]
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

            return $this->redirectToRoute('settings_general', ['_projectId' => $this->getActiveProject()->getId()]);
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

                return $this->redirectToRoute('settings_general', ['_projectId' => $this->getActiveProject()->getId()]);
            }
        }

        return $this->render('project_related/settings/reissue.html.twig', [
            'project' => $activeProject,
        ]);
    }
}