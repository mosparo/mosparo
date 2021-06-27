<?php

namespace Mosparo\Controller\ProjectRelated;

use Doctrine\ORM\QueryBuilder;
use Kir\StringUtils\Matching\Wildcards\Pattern;
use Mosparo\Entity\Project;
use Mosparo\Entity\ProjectMember;
use Mosparo\Entity\Rule;
use Mosparo\Entity\Submission;
use Mosparo\Entity\User;
use Mosparo\Form\ExtendedProjectFormType;
use Mosparo\Form\ProjectFormType;
use Mosparo\Util\TokenGenerator;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\BoolColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/settings")
 */
class SettingsController extends AbstractController implements ProjectRelatedInterface
{
    use ProjectRelatedTrait;

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

            // RuleSet the flash message
            $session = $request->getSession();
            $session->getFlashBag()->add('success', 'The settings were saved successfully.');

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
            ->add('user', TextColumn::class, ['label' => 'User', 'propertyPath' => 'user.email'])
            ->add('role', TwigColumn::class, [
                'label' => 'Role',
                'template' => 'project_related/settings/member/list/_role.html.twig'
            ])
            ->add('actions', TwigColumn::class, [
                'label' => 'Actions',
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
            'Reader' => ProjectMember::ROLE_READER,
            'Editor' => ProjectMember::ROLE_EDITOR,
            'Owner' => ProjectMember::ROLE_OWNER
        ];

        $form = $this->createFormBuilder($projectMember)
            ->add('email', EmailType::class, ['label' => 'Email address', 'mapped' => false, 'data' => $emailAddress, 'attr' => $emailFieldAttributes])
            ->add('role', ChoiceType::class, ['label' => 'Role', 'choices' => $projectMemberRoles, 'attr' => ['class' => 'form-select']])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $userRepository = $entityManager->getRepository(User::class);

            if ($isNew) {
                $user = $userRepository->findOneBy(['email' => $form->get('email')->getData()]);
                if ($user === null) {
                    $session = $request->getSession();
                    $session->getFlashBag()->add('error', 'The user was not found.');

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
                    $session->getFlashBag()->add('error', 'The project needs at least one owner.');

                    return $this->redirectToRoute('settings_member_list');
                }
            }

            $entityManager->flush();

            $session = $request->getSession();
            $session->getFlashBag()->add('success', 'The project member was saved successfully.');

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
                $session->getFlashBag()->add('error', 'The project needs at least one owner.');

                return $this->redirectToRoute('settings_member_list');
            }
        }

        if ($request->request->has('delete-token')) {
            $submittedToken = $request->request->get('delete-token');

            if ($this->isCsrfTokenValid('delete-project-member', $submittedToken)) {
                $entityManager = $this->getDoctrine()->getManager();

                $entityManager->remove($projectMember);
                $entityManager->flush();

                // RuleSet the flash message
                $session = $request->getSession();
                $session->getFlashBag()->add('error', 'The project member ' . $projectMember->getUser()->getEmail() . ' was removed successfully from the project.');

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

        $form = $this->createFormBuilder($config)
            // delay
            ->add('delayActive', CheckboxType::class, ['label' => 'Request delay active', 'required' => false])
            ->add('delayNumberOfRequests', NumberType::class, ['label' => 'Number of allowed requests', 'help' => 'The number of allowed requests before the delay come into place.'])
            ->add('delayDetectionTimeFrame', NumberType::class, ['label' => 'Detection time frame', 'help' => 'In seconds'])
            ->add('delayTime', NumberType::class, ['label' => 'Base delay time', 'help' => 'In seconds'])
            ->add('delayMultiplicator', NumberType::class, ['label' => 'Multiplicator', 'help' => 'The base delay time will be increased with every additional request.'])

            // lockout
            ->add('lockoutActive', CheckboxType::class, ['label' => 'Automatic lockout active', 'required' => false])
            ->add('lockoutNumberOfRequests', NumberType::class, ['label' => 'Number of allowed requests', 'help' => 'The number of allowed requests before the lockout come into place.'])
            ->add('lockoutDetectionTimeFrame', NumberType::class, ['label' => 'Detection time frame', 'help' => 'In seconds'])
            ->add('lockoutTime', NumberType::class, ['label' => 'Base lockout time', 'help' => 'In seconds'])
            ->add('lockoutMultiplicator', NumberType::class, ['label' => 'Multiplicator', 'help' => 'The base lockout time will be increased with every additional request.'])

            ->add('ipWhitelist', TextareaType::class, ['label' => 'Whitelisted IP addresses and subnets', 'required' => false, 'help' => 'Specify one ip address or subnet per line. Example: 192.168.1.13 or 192.168.1.0/24'])

            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $data = $form->getData();
            foreach ($data as $key => $value) {
                $project->setConfigValue($key, $value);
            }

            $entityManager->flush();

            // RuleSet the flash message
            $session = $request->getSession();
            $session->getFlashBag()->add('success', 'The security settings were saved successfully.');

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
        return $this->render('project_related/settings/design.html.twig');
    }

    /**
     * @Route("/reissue-keys", name="settings_reissue_keys")
     */
    public function reissueKeys(Request $request): Response
    {
        $activeProject = $this->projectHelper->getActiveProject();

        if (!$this->isGranted('ROLE_ADMIN') && !$activeProject->isProjectOwner($this->getUser())) {
            $session = $request->getSession();
            $session->getFlashBag()->add('error', 'Only an owner of the project can reissue the API keys.');

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

                // RuleSet the flash message
                $session = $request->getSession();
                $session->getFlashBag()->add('warning', 'The API keys were reissued successfully.');

                return $this->redirectToRoute('settings_general');
            }
        }

        return $this->render('project_related/settings/reissue.html.twig', [
            'project' => $activeProject,
        ]);
    }
}