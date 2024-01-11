<?php

namespace Mosparo\Controller\ProjectRelated;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Mosparo\Entity\Rule;
use Mosparo\Form\RuleAddMultipleItemsType;
use Mosparo\Form\RuleFormType;
use Mosparo\Rule\RuleTypeManager;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/project/{_projectId}/rules")
 */
class RuleController extends AbstractController implements ProjectRelatedInterface
{
    use ProjectRelatedTrait;

    protected EntityManagerInterface $entityManager;

    protected TranslatorInterface $translator;

    protected RuleTypeManager $ruleTypeManager;

    public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator, RuleTypeManager $ruleTypeManager)
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->ruleTypeManager = $ruleTypeManager;
    }

    /**
     * @Route("/", name="rule_list")
     * @Route("/filter/{filter}", name="rule_list_filtered")
     */
    public function index(Request $request, DataTableFactory $dataTableFactory, $filter = ''): Response
    {
        $filteredType = null;
        if (in_array($filter, $this->ruleTypeManager->getRuleTypeKeys())) {
            $filteredType = $filter;
        }

        $table = $dataTableFactory->create(['autoWidth' => true])
            ->add('name', TextColumn::class, ['label' => 'rule.list.name'])
            ->add('type', TwigColumn::class, [
                'label' => 'rule.list.type',
                'template' => 'project_related/rule/list/_rule_type.html.twig'
            ])
            ->add('status', TwigColumn::class, [
                'label' => 'rule.list.status',
                'template' => 'project_related/rule/list/_status.html.twig'
            ])
            ->add('actions', TwigColumn::class, [
                'label' => 'rule.list.actions',
                'className' => 'buttons',
                'template' => 'project_related/rule/list/_actions.html.twig'
            ])
            ->createAdapter(ORMAdapter::class, [
                'entity' => Rule::class,
                'query' => function (QueryBuilder $builder) use ($filteredType) {
                    $builder
                        ->select('e')
                        ->from(Rule::class, 'e');

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
            ->select('r.type, COUNT(r) AS countRules')
            ->from(Rule::class, 'r')
            ->groupBy('r.type');
        $numberOfRulesByType = [];
        foreach ($qb->getQuery()->getArrayResult() as $result) {
            $numberOfRulesByType[$result['type']] = $result['countRules'];
        }

        return $this->render('project_related/rule/list.html.twig', [
            'datatable' => $table,
            'ruleTypes' => $this->ruleTypeManager->getRuleTypes(),
            'numberOfRulesByType' => $numberOfRulesByType,
            'filter' => $filter
        ]);
    }

    /**
     * @Route("/create/choose-type", name="rule_create_choose_type")
     */
    public function createChooseType(RuleTypeManager $ruleTypeManager): Response
    {
        return $this->render('project_related/rule/create_choose_type.html.twig', [
            'ruleTypes' => $ruleTypeManager->getRuleTypes()
        ]);
    }

    /**
     * @Route("/create/{type}", name="rule_create_with_type")
     */
    public function createWithType(Request $request, $type, EntityManagerInterface $entityManager, RuleTypeManager $ruleTypeManager): Response
    {
        $ruleType = $ruleTypeManager->getRuleType($type);

        $rule = new Rule();
        $rule->setType($type);

        $form = $this->createForm(RuleFormType::class, $rule, [ 'rule_type' => $ruleType, 'locale' => $request->getLocale() ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($rule);
            $entityManager->flush();

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'rule.create.message.successfullyCreated',
                    [],
                    'mosparo'
                )
            );

            return $this->redirectToRoute('rule_list', ['_projectId' => $this->getActiveProject()->getId()]);
        }

        $addMultipleForm = $this->createForm(RuleAddMultipleItemsType::class, [], ['rule_type' => $ruleType]);

        return $this->render('project_related/rule/create_with_type.html.twig', [
            'rule' => $rule,
            'form' => $form->createView(),
            'addMultipleForm' => $addMultipleForm->createView(),
            'ruleType' => $ruleType
        ]);
    }

    /**
     * @Route("/{id}/edit", name="rule_edit")
     */
    public function edit(Request $request, Rule $rule, EntityManagerInterface $entityManager, RuleTypeManager $ruleTypeManager): Response
    {
        $readOnly = false;
        if (!$this->projectHelper->canManage()) {
            $readOnly = true;
        }

        $ruleType = $ruleTypeManager->getRuleType($rule->getType());

        $form = $this->createForm(RuleFormType::class, $rule, [ 'rule_type' => $ruleType, 'readonly' => $readOnly, 'locale' => $request->getLocale() ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && !$readOnly) {
            $entityManager->flush();

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'rule.edit.message.successfullySaved',
                    [],
                    'mosparo'
                )
            );

            return $this->redirectToRoute('rule_list', ['_projectId' => $this->getActiveProject()->getId()]);
        }

        $addMultipleForm = $this->createForm(RuleAddMultipleItemsType::class, [], ['rule_type' => $ruleType]);

        return $this->render('project_related/rule/edit.html.twig', [
            'rule' => $rule,
            'form' => $form->createView(),
            'addMultipleForm' => $addMultipleForm->createView(),
            'ruleType' => $ruleType
        ]);
    }

    /**
     * @Route("/{id}/delete", name="rule_delete")
     */
    public function delete(Request $request, Rule $rule, EntityManagerInterface $entityManager): Response
    {
        if ($request->request->has('delete-token')) {
            $submittedToken = $request->request->get('delete-token');

            if ($this->isCsrfTokenValid('delete-rule', $submittedToken)) {
                $entityManager->remove($rule);
                $entityManager->flush();

                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'success',
                    $this->translator->trans(
                        'rule.delete.message.successfullyDeleted',
                        ['%ruleName%' => $rule->getName()],
                        'mosparo'
                    )
                );

                return $this->redirectToRoute('rule_list', ['_projectId' => $this->getActiveProject()->getId()]);
            }
        }

        return $this->render('project_related/rule/delete.html.twig', [
            'rule' => $rule,
        ]);
    }
}
