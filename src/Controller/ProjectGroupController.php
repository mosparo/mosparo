<?php

namespace Mosparo\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\Entity\ProjectGroup;
use Mosparo\Form\ProjectGroupFormType;
use Mosparo\Helper\ProjectGroupHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/project-group')]
class ProjectGroupController extends AbstractController
{
    protected EntityManagerInterface $entityManager;

    protected TranslatorInterface $translator;

    public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
    }

    #[Route('/create', name: 'project_group_create')]
    #[Route('/{id}/edit', name: 'project_group_edit')]
    public function create(Request $request, ProjectGroupHelper $projectGroupHelper, ?ProjectGroup $projectGroup = null): Response
    {
        $parentGroup = null;
        $projectGroupRepository = $this->entityManager->getRepository(ProjectGroup::class);
        if ($request->query->has('parentGroup') && $request->query->get('parentGroup')) {
            $parentGroupId = $request->query->get('parentGroup');
            $parentGroup = $projectGroupRepository->find($parentGroupId);
        }

        $isNew = false;
        if ($projectGroup === null) {
            $projectGroup = new ProjectGroup();
            $projectGroup->setParent($parentGroup);
            $isNew = true;
        }

        $tree = $projectGroupHelper->getFullProjectGroupTreeForUser();
        $tree->sort();

        $form = $this->createForm(ProjectGroupFormType::class, $projectGroup, [
            'tree' => $tree,
            'active_group' => $projectGroup,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($projectGroup);
            $this->entityManager->flush();

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'projectGroup.form.message.successfullySaved',
                    ['%projectGroupName%' => $projectGroup->getName()],
                    'mosparo'
                )
            );

            if ($projectGroup->getParent()) {
                return $this->redirectToRoute('project_list_group', ['projectGroup' => $projectGroup->getParent()->getId()]);
            } else {
                return $this->redirectToRoute('project_list_root');
            }
        }

        return $this->render('project_group/create.html.twig', [
            'form' => $form->createView(),
            'projectGroup' => $projectGroup,
            'isNew' => $isNew,
        ]);
    }

    #[Route('/{id}/delete', name: 'project_group_delete')]
    public function delete(Request $request, ProjectGroup $projectGroup): Response
    {
        if ($request->request->has('delete-token')) {
            $submittedToken = $request->request->get('delete-token');

            if ($this->isCsrfTokenValid('delete-project-group', $submittedToken)) {
                $parentGroup = $projectGroup->getParent();

                foreach ($projectGroup->getChildren() as $child) {
                    $child->setParent($parentGroup);
                }

                foreach ($projectGroup->getProjects() as $project) {
                    $project->setProjectGroup($parentGroup);
                }

                $this->entityManager->remove($projectGroup);
                $this->entityManager->flush();

                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'success',
                    $this->translator->trans(
                        'projectGroup.delete.message.successfullyDeleted',
                        ['%projectGroupName%' => $projectGroup->getName()],
                        'mosparo'
                    )
                );

                if ($parentGroup) {
                    return $this->redirectToRoute('project_list_group', ['projectGroup' => $parentGroup->getId()]);
                } else {
                    return $this->redirectToRoute('project_list_root');
                }
            }
        }

        return $this->render('project_group/delete.html.twig', [
            'projectGroup' => $projectGroup,
        ]);
    }
}