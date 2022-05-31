<?php

namespace Mosparo\Controller\ProjectRelated;

use Doctrine\ORM\QueryBuilder;
use Mosparo\Entity\ProjectMember;
use Mosparo\Entity\User;
use Mosparo\Form\ExtendedProjectFormType;
use Mosparo\Util\ChoicesUtil;
use Mosparo\Util\TokenGenerator;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
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

    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @Route("/general", name="settings_general")
     */
    public function general(Request $request): Response
    {
        $project = $this->getActiveProject();

        $form = $this->createForm(ExtendedProjectFormType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
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
    public function memberModify(Request $request, ProjectMember $projectMember = null): Response
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
            $entityManager = $this->getDoctrine()->getManager();
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
    public function memberRemove(Request $request, ProjectMember $projectMember): Response
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
                $entityManager = $this->getDoctrine()->getManager();

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
    public function security(Request $request): Response
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
            $entityManager = $this->getDoctrine()->getManager();

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
    public function design(Request $request): Response
    {
        $project = $this->getActiveProject();
        $config = $project->getConfigValues();

        $boxSizeChoices = [
            'settings.design.choices.boxSize.small' => 'small',
            'settings.design.choices.boxSize.medium' => 'medium',
            'settings.design.choices.boxSize.large' => 'large',
        ];
        $form = $this->createFormBuilder($config, ['translation_domain' => 'mosparo'])
            ->add('boxSize', ChoiceType::class, ['label' => 'settings.design.form.boxSize', 'expanded' => true, 'choices' => $boxSizeChoices])
            ->add('boxRadius', NumberType::class, ['label' => 'settings.design.form.boxRadius', 'attr' => ['class' => 'text-end', 'min' => 0, 'data-variable' => '--mosparo-border-radius']])
            ->add('colorBackground', TextType::class, ['label' => 'settings.design.form.color.background', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-background-color']])
            ->add('colorBorder', TextType::class, ['label' => 'settings.design.form.color.border', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-border-color']])
            ->add('colorCheckbox', TextType::class, ['label' => 'settings.design.form.color.checkbox', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-circle-border-color']])
            ->add('colorText', TextType::class, ['label' => 'settings.design.form.color.text', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-text-color']])
            ->add('colorShadow', TextType::class, ['label' => 'settings.design.form.color.shadow', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-shadow-color']])
            ->add('colorShadowInset', TextType::class, ['label' => 'settings.design.form.color.shadowInset', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-shadow-inset-color']])
            ->add('colorFocusCheckbox', TextType::class, ['label' => 'settings.design.form.color.checkbox', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-focus-circle-border-color']])
            ->add('colorFocusCheckboxShadow', TextType::class, ['label' => 'settings.design.form.color.checkboxShadow', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-focus-circle-shadow-color']])
            ->add('colorLoadingCheckbox', TextType::class, ['label' => 'settings.design.form.color.checkbox', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-loading-circle-border-color']])
            ->add('colorLoadingCheckboxAnimatedCircle', TextType::class, ['label' => 'settings.design.form.color.checkboxAnimatedCircle', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-loading-circle-animated-border-color']])
            ->add('colorSuccessBackground', TextType::class, ['label' => 'settings.design.form.color.background', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-success-background-color']])
            ->add('colorSuccessBorder', TextType::class, ['label' => 'settings.design.form.color.border', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-success-border-color']])
            ->add('colorSuccessCheckbox', TextType::class, ['label' => 'settings.design.form.color.checkbox', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-success-circle-border-color']])
            ->add('colorSuccessText', TextType::class, ['label' => 'settings.design.form.color.text', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-success-text-color']])
            ->add('colorSuccessShadow', TextType::class, ['label' => 'settings.design.form.color.shadow', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-success-shadow-color']])
            ->add('colorSuccessShadowInset', TextType::class, ['label' => 'settings.design.form.color.shadowInset', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-success-shadow-inset-color']])
            ->add('colorFailureBackground', TextType::class, ['label' => 'settings.design.form.color.background', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-failure-background-color']])
            ->add('colorFailureBorder', TextType::class, ['label' => 'settings.design.form.color.border', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-failure-border-color']])
            ->add('colorFailureCheckbox', TextType::class, ['label' => 'settings.design.form.color.checkbox', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-failure-circle-border-color']])
            ->add('colorFailureText', TextType::class, ['label' => 'settings.design.form.color.text', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-failure-text-color']])
            ->add('colorFailureShadow', TextType::class, ['label' => 'settings.design.form.color.shadow', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-failure-shadow-color']])
            ->add('colorFailureShadowInset', TextType::class, ['label' => 'settings.design.form.color.shadowInset', 'required' => false, 'attr' => ['class' => 'colorpicker', 'data-variable' => '--mosparo-failure-shadow-inset-color']])
            ->add('showPingAnimation', CheckboxType::class, ['label' => 'settings.design.form.showPingAnimation', 'required' => false, 'attr' => ['data-variable' => '--mosparo-ping-animation-name', 'data-variable-value' => 'mosparo__ping-animation']])
            ->add('showMosparoLogo', CheckboxType::class, ['label' => 'settings.design.form.showMosparoLogo', 'required' => false, 'attr' => ['data-variable' => '--mosparo-show-logo', 'data-variable-value' => 'block']])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $data = $form->getData();
            foreach ($data as $key => $value) {
                $project->setConfigValue($key, $value);
            }

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

        $sizeVariables = [
            'small' => [
                '--mosparo-font-size' => 12,
                '--mosparo-line-height' => 16,
                '--mosparo-padding-top' => 16,
                '--mosparo-padding-left' => 20,
                '--mosparo-padding-right' => 16,
                '--mosparo-padding-bottom' => 16,
                '--mosparo-border-radius' => 8,
                '--mosparo-border-width' => 2,
                '--mosparo-container-min-width' => 250,
                '--mosparo-container-max-width' => 430,
                '--mosparo-circle-size' => 32,
                '--mosparo-circle-radius' => 16,
                '--mosparo-circle-border-width' => 2,
                '--mosparo-circle-offset' => -2,
                '--mosparo-circle-margin-right' => 16,
                '--mosparo-shadow-blur-radius' => 8,
                '--mosparo-shadow-spread-radius' => 2,
                '--mosparo-icon-border-offset' => -1,
                '--mosparo-icon-border-width' => 2,
                '--mosparo-checkmark-icon-height' => 8,
                '--mosparo-logo-left' => 9,
                '--mosparo-logo-bottom' => 1,
                '--mosparo-logo-width' => 55,
                '--mosparo-logo-height' => 10,
            ],
            'medium' => [
                '--mosparo-font-size' => 16,
                '--mosparo-line-height' => 22,
                '--mosparo-padding-top' => 20,
                '--mosparo-padding-left' => 24,
                '--mosparo-padding-right' => 20,
                '--mosparo-padding-bottom' => 20,
                '--mosparo-border-radius' => 11,
                '--mosparo-border-width' => 3,
                '--mosparo-container-min-width' => 320,
                '--mosparo-container-max-width' => 500,
                '--mosparo-circle-size' => 40,
                '--mosparo-circle-radius' => 20,
                '--mosparo-circle-border-width' => 3,
                '--mosparo-circle-offset' => -3,
                '--mosparo-circle-margin-right' => 20,
                '--mosparo-shadow-blur-radius' => 12,
                '--mosparo-shadow-spread-radius' => 3,
                '--mosparo-icon-border-offset' => -1,
                '--mosparo-icon-border-width' => 2,
                '--mosparo-checkmark-icon-height' => 10,
                '--mosparo-logo-left' => 10,
                '--mosparo-logo-bottom' => 5,
                '--mosparo-logo-width' => 70,
                '--mosparo-logo-height' => 15,
            ],
            'large' => [
                '--mosparo-font-size' => 24,
                '--mosparo-line-height' => 32,
                '--mosparo-padding-top' => 26,
                '--mosparo-padding-left' => 30,
                '--mosparo-padding-right' => 26,
                '--mosparo-padding-bottom' => 26,
                '--mosparo-border-radius' => 16,
                '--mosparo-border-width' => 4,
                '--mosparo-container-min-width' => 390,
                '--mosparo-container-max-width' => 570,
                '--mosparo-circle-size' => 44,
                '--mosparo-circle-radius' => 22,
                '--mosparo-circle-border-width' => 4,
                '--mosparo-circle-offset' => -4,
                '--mosparo-circle-margin-right' => 24,
                '--mosparo-shadow-blur-radius' => 16,
                '--mosparo-shadow-spread-radius' => 4,
                '--mosparo-icon-border-offset' => -2,
                '--mosparo-icon-border-width' => 4,
                '--mosparo-checkmark-icon-height' => 11,
                '--mosparo-logo-left' => 15,
                '--mosparo-logo-bottom' => 10,
                '--mosparo-logo-width' => 75,
                '--mosparo-logo-height' => 15,
            ],
        ];

        return $this->render('project_related/settings/design.html.twig', [
            'form' => $form->createView(),
            'project' => $project,
            'sizeVariables' => $sizeVariables,
        ]);
    }

    /**
     * @Route("/reissue-keys", name="settings_reissue_keys")
     */
    public function reissueKeys(Request $request): Response
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
                $entityManager = $this->getDoctrine()->getManager();

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