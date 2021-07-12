<?php

namespace Mosparo\Controller\ProjectRelated;

use Mosparo\Entity\Project;
use Mosparo\Entity\Rule;
use Mosparo\Entity\Ruleset;
use Mosparo\Exception;
use Mosparo\Form\RuleAddMultipleItemsType;
use Mosparo\Form\RuleFormType;
use Mosparo\Form\RulesetFormType;
use Mosparo\Helper\RulesetHelper;
use Mosparo\Repository\RuleRepository;
use Mosparo\Rule\RuleTypeManager;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\BoolColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/rulesets")
 */
class RulesetController extends AbstractController implements ProjectRelatedInterface
{
    use ProjectRelatedTrait;

    protected $rulesetHelper;

    protected $translator;

    public function __construct(RulesetHelper $rulesetHelper, TranslatorInterface $translator)
    {
        $this->rulesetHelper = $rulesetHelper;
        $this->translator = $translator;
    }

    /**
     * @Route("/", name="ruleset_list")
     */
    public function index(Request $request, DataTableFactory $dataTableFactory): Response
    {
        $table = $dataTableFactory->create(['autoWidth' => true])
            ->add('name', TextColumn::class, ['label' => 'ruleset.list.name'])
            ->add('status', TwigColumn::class, [
                'label' => 'ruleset.list.status',
                'template' => 'project_related/ruleset/list/_status.html.twig',
            ])
            ->add('refreshedAt', TwigColumn::class, [
                'label' => 'ruleset.list.refreshedAt',
                'propertyPath' => 'rulesetCache.refreshedAt',
                'template' => 'project_related/ruleset/list/_date.html.twig',
            ])
            ->add('updatedAt', TwigColumn::class, [
                'label' => 'ruleset.list.updatedAt',
                'propertyPath' => 'rulesetCache.updatedAt',
                'template' => 'project_related/ruleset/list/_date.html.twig',
            ])
            ->add('actions', TwigColumn::class, [
                'label' => 'ruleset.list.actions',
                'className' => 'buttons',
                'template' => 'project_related/ruleset/list/_actions.html.twig'
            ])
            ->createAdapter(ORMAdapter::class, [
                'entity' => Ruleset::class,
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('project_related/ruleset/list.html.twig', [
            'datatable' => $table
        ]);
    }

    /**
     * @Route("/add", name="ruleset_add")
     * @Route("/{id}/edit", name="ruleset_edit")
     */
    public function form(Request $request, Ruleset $ruleset = null): Response
    {
        $isNew = false;
        if ($ruleset === null) {
            $ruleset = new Ruleset();
            $ruleset->setSTatus(true);
            $isNew = true;
        }

        $form = $this->createForm(RulesetFormType::class, $ruleset);
        $form->handleRequest($request);

        $hasError = false;
        $errorMessage = '';
        if ($form->isSubmitted() && $form->isValid()) {
            if ($isNew) {
                $this->getDoctrine()->getManager()->persist($ruleset);
            }

            try {
                $this->rulesetHelper->downloadRuleset($ruleset);
            } catch (Exception $e) {
                $hasError = true;
                $errorMessage = $e->getMessage();
            }

            if (!$hasError) {
                $this->getDoctrine()->getManager()->flush();

                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'success',
                    $this->translator->trans(
                        'ruleset.form.message.successfullySaved',
                        [],
                        'mosparo'
                    )
                );

                return $this->redirectToRoute('ruleset_list');
            }
        }

        return $this->render('project_related/ruleset/form.html.twig', [
            'ruleset' => $ruleset,
            'form' => $form->createView(),
            'isNew' => $isNew,
            'hasError' => $hasError,
            'errorMessage' => $errorMessage
        ]);
    }

    /**
     * @Route("/{id}/delete", name="ruleset_delete")
     */
    public function delete(Request $request, Ruleset $ruleset): Response
    {
        if ($request->request->has('delete-token')) {
            $submittedToken = $request->request->get('delete-token');

            if ($this->isCsrfTokenValid('delete-ruleset', $submittedToken)) {
                $entityManager = $this->getDoctrine()->getManager();

                $entityManager->remove($ruleset);
                $entityManager->flush();

                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'error',
                    $this->translator->trans(
                        'ruleset.delete.message.successfullyDeleted',
                        ['%rulesetName%' => $ruleset->getName()],
                        'mosparo'
                    )
                );

                return $this->redirectToRoute('ruleset_list');
            }
        }

        return $this->render('project_related/ruleset/delete.html.twig', [
            'ruleset' => $ruleset,
        ]);
    }

    /**
     * @Route("/{id}/view", name="ruleset_view")
     */
    public function view(Request $request, Ruleset $ruleset): Response
    {
        $hasError = false;
        $errorMessage = '';
        try {
            $result = $this->rulesetHelper->downloadRuleset($ruleset);

            if ($result) {
                $this->getDoctrine()->getManager()->flush();
            }
        } catch (Exception $e) {
            $hasError = true;
            $errorMessage = $e->getMessage();
        }

        return $this->render('project_related/ruleset/view.html.twig', [
            'ruleset' => $ruleset,
            'hasError' => $hasError,
            'errorMessage' => $errorMessage
        ]);
    }
}
