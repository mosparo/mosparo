<?php

namespace Mosparo\Controller\ProjectRelated;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Mosparo\Entity\Ruleset;
use Mosparo\Entity\RulesetRuleCache;
use Mosparo\Entity\RulesetRuleItemCache;
use Mosparo\Exception;
use Mosparo\Form\RulesetFormType;
use Mosparo\Helper\RulesetHelper;
use Mosparo\Rule\RuleTypeManager;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\NumberColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/project/{_projectId}/rulesets")
 */
class RulesetController extends AbstractController implements ProjectRelatedInterface
{
    use ProjectRelatedTrait;

    protected EntityManagerInterface $entityManager;

    protected DataTableFactory $dataTableFactory;

    protected RuleTypeManager $ruleTypeManager;

    protected RulesetHelper $rulesetHelper;

    protected TranslatorInterface $translator;

    public function __construct(
        EntityManagerInterface $entityManager,
        DataTableFactory $dataTableFactory,
        RuleTypeManager $ruleTypeManager,
        RulesetHelper $rulesetHelper,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->dataTableFactory = $dataTableFactory;
        $this->ruleTypeManager = $ruleTypeManager;
        $this->rulesetHelper = $rulesetHelper;
        $this->translator = $translator;
    }

    /**
     * @Route("/", name="ruleset_list")
     */
    public function index(Request $request): Response
    {
        $table = $this->dataTableFactory->create(['autoWidth' => true])
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
            $ruleset->setStatus(true);
            $isNew = true;
        }

        $form = $this->createForm(RulesetFormType::class, $ruleset);
        $form->handleRequest($request);

        $hasError = false;
        $errorMessage = '';
        if ($form->isSubmitted() && $form->isValid()) {
            if ($isNew) {
                $this->entityManager->persist($ruleset);
            }

            try {
                $this->rulesetHelper->downloadRuleset($ruleset);
            } catch (Exception $e) {
                $hasError = true;
                $errorMessage = $e->getMessage();
            }

            if (!$hasError) {
                $this->entityManager->flush();

                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'success',
                    $this->translator->trans(
                        'ruleset.form.message.successfullySaved',
                        [],
                        'mosparo'
                    )
                );

                return $this->redirectToRoute('ruleset_list', ['_projectId' => $this->getActiveProject()->getId()]);
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
                $this->entityManager->remove($ruleset);
                $this->entityManager->flush();

                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'success',
                    $this->translator->trans(
                        'ruleset.delete.message.successfullyDeleted',
                        ['%rulesetName%' => $ruleset->getName()],
                        'mosparo'
                    )
                );

                return $this->redirectToRoute('ruleset_list', ['_projectId' => $this->getActiveProject()->getId()]);
            }
        }

        return $this->render('project_related/ruleset/delete.html.twig', [
            'ruleset' => $ruleset,
        ]);
    }

    /**
     * @Route("/{id}/view", name="ruleset_view")
     * @Route("/{id}/view/filter/{filter}", name="ruleset_view_filtered")
     */
    public function view(Request $request, Ruleset $ruleset, $filter = ''): Response
    {
        $hasError = false;
        $errorMessage = '';
        try {
            $result = $this->rulesetHelper->downloadRuleset($ruleset);

            if ($result) {
                $this->entityManager->flush();
            }
        } catch (Exception $e) {
            $hasError = true;
            $errorMessage = $e->getMessage();
        }

        $filteredType = null;
        if (in_array($filter, $this->ruleTypeManager->getRuleTypeKeys())) {
            $filteredType = $filter;
        }

        $table = $this->dataTableFactory->create(['autoWidth' => true])
            ->add('name', TextColumn::class, ['label' => 'ruleset.view.list.rules.name'])
            ->add('type', TwigColumn::class, [
                'label' => 'ruleset.view.list.rules.type',
                'template' => 'project_related/ruleset/view/rule_list/_type.html.twig'
            ])
            ->add('numberOfRuleItems', TwigColumn::class, [
                'label' => 'ruleset.view.list.rules.numberOfRuleItems',
                'template' => 'project_related/ruleset/view/rule_list/_numberOfRuleItems.html.twig'
            ])
            ->add('spamRatingFactor', NumberColumn::class, ['label' => 'ruleset.view.list.rules.spamRatingFactor'])
            ->add('actions', TwigColumn::class, [
                'label' => 'ruleset.view.list.rules.actions',
                'className' => 'buttons',
                'template' => 'project_related/ruleset/view/rule_list/_actions.html.twig'
            ])
            ->createAdapter(ORMAdapter::class, [
                'entity' => RulesetRuleCache::class,
                'query' => function (QueryBuilder $builder) use ($ruleset, $filteredType) {
                    $builder
                        ->select('e')
                        ->from(RulesetRuleCache::class, 'e')
                        ->where('e.rulesetCache = :rulesetCache')
                        ->setParameter('rulesetCache', $ruleset->getRulesetCache());

                    if ($filteredType !== null) {
                        $builder
                            ->andWhere('e.type = :filteredType')
                            ->setParameter('filteredType', $filteredType);
                    }
                },
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        // Count the rule types
        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('rrc.type, COUNT(rrc) AS countRules')
            ->from(RulesetRuleCache::class, 'rrc')
            ->where('rrc.rulesetCache = :rulesetCache')
            ->groupBy('rrc.type')
            ->setParameter('rulesetCache', $ruleset->getRulesetCache());
        $numberOfRulesByType = [];
        foreach ($qb->getQuery()->getArrayResult() as $result) {
            $numberOfRulesByType[$result['type']] = $result['countRules'];
        }

        return $this->render('project_related/ruleset/view.html.twig', [
            'ruleset' => $ruleset,
            'hasError' => $hasError,
            'errorMessage' => $errorMessage,
            'datatable' => $table,
            'ruleTypes' => $this->ruleTypeManager->getRuleTypes(),
            'numberOfRulesByType' => $numberOfRulesByType,
            'filter' => $filter,
        ]);
    }

    /**
     * @Route("/{id}/view/rule/{ruleUuid}", name="ruleset_view_rule")
     */
    public function viewRule(Request $request, Ruleset $ruleset, string $ruleUuid): Response
    {
        $rulesetRuleCacheRepository = $this->entityManager->getRepository(RulesetRuleCache::class);
        $rulesetRuleCache = $rulesetRuleCacheRepository->findOneBy(['uuid' => $ruleUuid]);

        if (!$rulesetRuleCache) {
            return $this->redirectToRoute('ruleset_view', ['_projectId' => $this->getActiveProject()->getId(), 'id' => $ruleset->getId()]);
        }

        $table = $this->dataTableFactory->create(['autoWidth' => true])
            ->add('type', TwigColumn::class, [
                'label' => 'ruleset.view.list.ruleItems.type',
                'template' => 'project_related/ruleset/view/rule_item_list/_type.html.twig'
            ])
            ->add('value', TwigColumn::class, [
                'label' => 'ruleset.view.list.ruleItems.value',
                'template' => 'project_related/ruleset/view/rule_item_list/_value.html.twig'
            ])
            ->add('spamRatingFactor', NumberColumn::class, ['label' => 'ruleset.view.list.ruleItems.spamRatingFactor'])
            ->createAdapter(ORMAdapter::class, [
                'entity' => RulesetRuleItemCache::class,
                'query' => function (QueryBuilder $builder) use ($rulesetRuleCache) {
                    $builder
                        ->select('e')
                        ->from(RulesetRuleItemCache::class, 'e')
                        ->where('e.rulesetRuleCache = :rulesetRuleCache')
                        ->setParameter('rulesetRuleCache', $rulesetRuleCache);
                },
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('project_related/ruleset/view_rule.html.twig', [
            'ruleset' => $ruleset,
            'rule' => $rulesetRuleCache,
            'datatable' => $table
        ]);
    }
}
