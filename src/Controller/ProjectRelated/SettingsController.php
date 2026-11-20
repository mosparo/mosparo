<?php

namespace Mosparo\Controller\ProjectRelated;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Mosparo\Entity\ProjectMember;
use Mosparo\Entity\SecurityGuideline;
use Mosparo\Entity\Translation;
use Mosparo\Entity\User;
use Mosparo\Enum\TranslationKey;
use Mosparo\Form\AdvancedProjectFormType;
use Mosparo\Form\DesignSettingsFormType;
use Mosparo\Form\ProjectFormType;
use Mosparo\Form\SecurityGuidelineFormType;
use Mosparo\Form\SecuritySettingsFormType;
use Mosparo\Helper\DesignHelper;
use Mosparo\Helper\GeoIp2Helper;
use Mosparo\Helper\ProjectGroupHelper;
use Mosparo\UserInterface\GridTable\Adapter\OrmAdapter;
use Mosparo\UserInterface\GridTable\Column;
use Mosparo\UserInterface\GridTable\Factory;
use Mosparo\Util\TokenGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/project/{_projectId}/settings')]
class SettingsController extends AbstractController implements ProjectRelatedInterface
{
    use ProjectRelatedTrait;

    protected EntityManagerInterface $entityManager;

    protected Factory $factory;

    protected TranslatorInterface $translator;

    public function __construct(EntityManagerInterface $entityManager, Factory $factory, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->factory = $factory;
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
    public function memberList(): Response
    {
        $project = $this->getActiveProject();

        $adapter = (new OrmAdapter($this->entityManager, ProjectMember::class))
            ->setQueryCallback(function (QueryBuilder $qb) use ($project) {
                $qb
                    ->addSelect('u')
                    ->innerJoin('e.user', 'u')
                    ->where('e.project = :project')
                    ->setParameter('project', $project)
                ;
            })
        ;

        $table = $this->factory->create($adapter)
            ->addColumn(new Column(
                'u.email',
                'settings.projectMember.list.user',
                mapped: false,
                template: 'project_related/settings/member/list/_user.html.twig',
            ))
            ->addColumn(new Column(
                'role',
                'settings.projectMember.list.role',
                template: 'project_related/settings/member/list/_role.html.twig',
            ))
            ->addColumn(new Column(
                'actions',
                'settings.projectMember.list.actions',
                sortable: false,
                mapped: false,
                template: 'project_related/settings/member/list/_actions.html.twig',
                cellClass: 'collapsed-label-invisible action-buttons',
            ))
            ->setSortBy('u.email')
        ;

        $table->query();

        return $this->render('project_related/settings/member/list.html.twig', [
            'project' => $project,
            'table' => $table
        ]);
    }

    #[Route('/members/add', name: 'settings_member_add')]
    #[Route('/members/{id}/edit', name: 'settings_member_edit')]
    public function memberModify(Request $request, EntityManagerInterface $entityManager, ?ProjectMember $projectMember = null): Response
    {
        if ($projectMember && $projectMember->getProject() !== $this->getActiveProject()) {
            throw new NotFoundHttpException();
        }

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
                } else if ($user->getProjectMemberships()) {
                    foreach ($user->getProjectMemberships() as $membership) {
                        if ($membership->getProject()->getId() === $this->getActiveProject()->getId()) {
                            $session = $request->getSession();
                            $session->getFlashBag()->add(
                                'error',
                                $this->translator->trans(
                                    'settings.projectMember.form.message.errorUserAlreadyMember',
                                    [],
                                    'mosparo'
                                )
                            );

                            return $this->redirectToRoute('settings_member_list', ['_projectId' => $this->getActiveProject()->getId()]);
                        }
                    }
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
        if ($projectMember->getProject() !== $this->getActiveProject()) {
            throw new NotFoundHttpException();
        }

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
                    'success',
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
    public function security(): Response
    {
        $project = $this->getActiveProject();

        $adapter = (new OrmAdapter($this->entityManager, SecurityGuideline::class))
            ->setQueryCallback(function (QueryBuilder $qb) use ($project) {
                $qb
                    ->where('e.project = :project')
                    ->setParameter('project', $project)
                ;
            })
        ;

        $table = $this->factory->create($adapter)
            ->addColumn(new Column(
                'name',
                'settings.security.guideline.list.name',
            ))
            ->addColumn(new Column(
                'priority',
                'settings.security.guideline.list.priority',
                isNumeric: true,
            ))
            ->addColumn(new Column(
                'actions',
                'settings.security.guideline.list.actions',
                sortable: false,
                mapped: false,
                template: 'project_related/settings/security/list/_actions.html.twig',
                cellClass: 'collapsed-label-invisible action-buttons',
            ))
            ->setSortBy('priority', 'DESC')
        ;

        $table->query();

        return $this->render('project_related/settings/security/security.html.twig', [
            'project' => $project,
            'table' => $table
        ]);
    }

    #[Route('/security/edit-general', name: 'settings_security_edit_general')]
    public function securityEditGeneralForm(Request $request, EntityManagerInterface $entityManager): Response
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
    public function securityGuidelineForm(Request $request, EntityManagerInterface $entityManager, GeoIp2Helper $geoIp2Helper, ?SecurityGuideline $securityGuideline = null): Response
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
                    'success',
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

    #[Route('/design/switch-mode', name: 'settings_design_switch_mode', methods: ['POST'])]
    public function switchDesignMode(Request $request, EntityManagerInterface $entityManager): Response
    {
        $project = $this->getActiveProject();
        $token = $request->request->getString('_token');
        $mode = $request->request->getString('mode');

        if ($this->isCsrfTokenValid('design-switch-mode', $token) && in_array($mode, ['simple', 'advanced', 'invisible-simple'], true)) {
            $project->setConfigValue('designMode', $mode);

            $entityManager->flush();
        }

        return $this->redirectToRoute('settings_design', ['_projectId' => $this->getActiveProject()->getId()]);
    }

    #[Route('/translations', name: 'settings_translation_list')]
    public function translationList(): Response
    {
        $project = $this->getActiveProject();

        $adapter = (new OrmAdapter($this->entityManager, Translation::class))
            ->setQueryCallback(function (QueryBuilder $qb) use ($project) {
                $qb
                    ->where('e.project = :project')
                    ->setParameter('project', $project)
                ;
            })
        ;

        $table = $this->factory->create($adapter)
            ->addColumn(new Column(
                'locale',
                'settings.translation.list.locale',
            ))
            ->addColumn(new Column(
                'translationKey',
                'settings.translation.list.translationKey',
                template: 'project_related/settings/translation/list/_translationKey.html.twig',
            ))
            ->addColumn(new Column(
                'text',
                'settings.translation.list.text',
            ))
            ->addColumn(new Column(
                'actions',
                'settings.translation.list.actions',
                sortable: false,
                mapped: false,
                template: 'project_related/settings/translation/list/_actions.html.twig',
                cellClass: 'collapsed-label-invisible action-buttons',
            ))
            ->setSortBy('locale', 'DESC')
        ;

        $table->query();

        return $this->render('project_related/settings/translation/list.html.twig', [
            'project' => $project,
            'table' => $table
        ]);
    }

    #[Route('/translations/add', name: 'settings_translation_add')]
    #[Route('/translations/{id}/edit', name: 'settings_translation_edit')]
    public function translationModify(Request $request, EntityManagerInterface $entityManager, ?Translation $translation = null): Response
    {
        $isNew = false;
        if ($translation === null) {
            $translation = new Translation();
            $translation->setProject($this->getActiveProject());
            $isNew = true;

            $entityManager->persist($translation);
        }

        $form = $this->createFormBuilder($translation, ['translation_domain' => 'mosparo'])
            ->add('locale', TextType::class, [
                'label' => 'settings.translation.form.locale',
                'help' => 'settings.translation.form.localeHelp',
                'attr' => [
                    'placeholder' => $this->translator->trans('settings.translation.form.localePlaceholder', [], 'mosparo'),
                    'maxlength' => 8,
                ]
            ])
            ->add('translationKey', EnumType::class, [
                'label' => 'settings.translation.form.translationKey',
                'class' => TranslationKey::class,
                'group_by' => function (TranslationKey $choice, int $key, string $value): ?string {
                    if (str_starts_with($choice->name, 'ACCESSIBILITY_')) {
                        return 'settings.translation.form.translationKeyGroup.accessibility';
                    } else if (str_starts_with($choice->name, 'ERROR_')) {
                        return 'settings.translation.form.translationKeyGroup.error';
                    } else if (str_starts_with($choice->name, 'HONEY_POT_')) {
                        return 'settings.translation.form.translationKeyGroup.honeyPot';
                    }

                    return 'settings.translation.form.translationKeyGroup.mainLabel';
                }
            ])
            ->add('text', TextType::class, ['label' => 'settings.translation.form.text'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'settings.translation.form.message.successfullySaved',
                    [],
                    'mosparo'
                )
            );

            return $this->redirectToRoute('settings_translation_list', ['_projectId' => $this->getActiveProject()->getId()]);
        }

        return $this->render('project_related/settings/translation/form.html.twig', [
            'translation' => $translation,
            'form' => $form->createView(),
            'isNew' => $isNew,
        ]);
    }

    #[Route('/translations/{id}/remove', name: 'settings_translation_remove')]
    public function translationRemove(Request $request, EntityManagerInterface $entityManager, Translation $translation): Response
    {
        if ($request->request->has('delete-token')) {
            $submittedToken = $request->request->get('delete-token');

            if ($this->isCsrfTokenValid('delete-translation', $submittedToken)) {
                $entityManager->remove($translation);
                $entityManager->flush();

                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'success',
                    $this->translator->trans(
                        'settings.translation.delete.message.successfullyRemoved',
                        [],
                        'mosparo'
                    )
                );

                return $this->redirectToRoute('settings_translation_list', ['_projectId' => $this->getActiveProject()->getId()]);
            }
        }

        return $this->render('project_related/settings/translation/remove.html.twig', [
            'translation' => $translation,
        ]);
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
                    'success',
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
