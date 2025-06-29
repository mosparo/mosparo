<?php

namespace Mosparo\Controller\ProjectRelated;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Kir\StringUtils\Matching\Wildcards\Pattern;
use Mosparo\Entity\Rule;
use Mosparo\Entity\RuleItem;
use Mosparo\Form\RuleFormType;
use Mosparo\Rule\RuleTypeManager;
use Mosparo\Rule\Type\RuleTypeInterface;
use Mosparo\Rule\Type\UnicodeBlockRuleType;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/project/{_projectId}/rules')]
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

    #[Route('/', name: 'rule_list')]
    #[Route('/filter/{filter}', name: 'rule_list_filtered')]
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
            ->addOrderBy('name')
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

    #[Route('/create/choose-type', name: 'rule_create_choose_type')]
    public function createChooseType(RuleTypeManager $ruleTypeManager): Response
    {
        return $this->render('project_related/rule/create_choose_type.html.twig', [
            'ruleTypes' => $ruleTypeManager->getRuleTypes()
        ]);
    }

    #[Route('/create/{type}', name: 'rule_create_with_type')]
    public function createWithType(Request $request, $type, EntityManagerInterface $entityManager): Response
    {
        $ruleType = $this->ruleTypeManager->getRuleType($type);

        $rule = new Rule();
        $rule->setType($type);

        $form = $this->createFormBuilder($rule, ['translation_domain' => 'mosparo'])
            ->add('name', TextType::class, ['label' => 'rule.form.rule.name'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($rule);
            $entityManager->flush();

            return $this->redirectToRoute('rule_edit', ['_projectId' => $this->getActiveProject()->getId(), 'id' => $rule->getId()]);
        }

        return $this->render('project_related/rule/create_with_type.html.twig', [
            'rule' => $rule,
            'form' => $form->createView(),
            'ruleType' => $ruleType
        ]);
    }

    #[Route('/{id}/edit', name: 'rule_edit')]
    public function edit(Request $request, Rule $rule, EntityManagerInterface $entityManager): Response
    {
        $readOnly = false;
        if (!$this->projectHelper->canManage()) {
            $readOnly = true;
        }

        $ruleType = $this->ruleTypeManager->getRuleType($rule->getType());

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

        $valueOptions = $this->getValueOptions($request, $ruleType);

        return $this->render('project_related/rule/edit.html.twig', [
            'rule' => $rule,
            'typeOptions' => $this->buildFrontendChoices($ruleType->getSubtypes()),
            'valueOptions' => $valueOptions,
            'validatorPattern' => $ruleType->getValidatorPattern(),
            'form' => $form->createView(),
            'ruleType' => $ruleType
        ]);
    }

    #[Route('/{id}/load-items', name: 'rule_load_items')]
    public function loadItems(Request $request, Rule $rule, EntityManagerInterface $entityManager): Response
    {
        $submittedToken = $request->query->get('token');
        if (!$this->isCsrfTokenValid('manage-rule', $submittedToken)) {
            return new Response(null, 401);
        }

        $page = $request->query->get('page', 1);
        $itemsPerPage = $request->query->get('size', 10);

        $data = [];
        $ruleType = $this->ruleTypeManager->getRuleType($rule->getType());

        $qb = $entityManager->createQueryBuilder();
        $qb
            ->from('Mosparo\Entity\RuleItem', 'ri')
            ->andWhere('ri.rule = :rule')
            ->setParameter('rule', $rule);

        $queryData = $request->query->all();
        $activeFilters = $queryData['activeFilters'] ?? [];

        if (is_array($activeFilters) && $activeFilters) {
            if (isset($activeFilters['type'])) {
                $qb
                    ->andWhere('ri.type = :type')
                    ->setParameter('type', $activeFilters['type']);
            }

            if (isset($activeFilters['value'])) {
                $valueOptions = $this->getValueOptions($request, $ruleType);

                if ($valueOptions) {
                    $matchingValues = $this->findMatchingValues($valueOptions, $activeFilters['value']);
                    $qb
                        ->andWhere('ri.value IN (:values)')
                        ->setParameter('values', $matchingValues, ArrayParameterType::STRING);
                } else {
                    $qb
                        ->andWhere('ri.value LIKE :value')
                        ->setParameter('value', str_replace('*', '%', $activeFilters['value']));
                }
            }

            if (isset($activeFilters['srfMin'])) {
                $qb
                    ->andWhere('ri.spamRatingFactor > :srfMin')
                    ->setParameter('srfMin', intval($activeFilters['srfMin']));
            }

            if (isset($activeFilters['srfMax'])) {
                $qb
                    ->andWhere('ri.spamRatingFactor < :srfMax')
                    ->setParameter('srfMax', intval($activeFilters['srfMax']));
            }
        }

        $countQb = clone $qb;
        $countQb->select('COUNT(ri.id) as count');
        $numberOfItems = $countQb->getQuery()->getSingleScalarResult();

        $qb
            ->select('ri')
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage);

        $sort = $queryData['sort'] ?? [];
        if (is_array($sort) && $sort) {
            $sort = current($sort);

            $allowedFields = ['type', 'value', 'spamRatingFactor'];
            $allowedDirections = ['asc', 'desc'];

            if (in_array($sort['field'], $allowedFields) && in_array($sort['dir'], $allowedDirections)) {
                $qb->orderBy('ri.' . $sort['field'], $sort['dir']);
            }
        }

        foreach ($qb->getQuery()->toIterable() as $item) {
            $data[] = [
                'id' => $item->getId(),
                'uuid' => $item->getUuid(),
                'type' => $item->getType(),
                'value' => $item->getValue(),
                'spamRatingFactor' => $item->getSpamRatingFactor(),
            ];

            $entityManager->detach($item);
        }

        return new JsonResponse([
            'data' => $data,
            'last_page' => ceil($numberOfItems / $itemsPerPage),
        ]);
    }

    #[Route('/{id}/save-changes', name: 'rule_edit_save_changes')]
    public function saveChanges(Request $request, Rule $rule, EntityManagerInterface $entityManager): Response
    {
        $submittedToken = $request->request->get('token');
        if (!$this->isCsrfTokenValid('save-changes', $submittedToken)) {
            return new Response(null, 401);
        }

        $changes = $request->request->all()['changes'] ?? null;
        if (!$changes) {
            return new Response(null, 400);
        }

        $processed = [];
        $newItems = [];
        foreach ($changes as $change) {
            $type = $change['type'] ?? null;
            if (!$type) {
                continue;
            }

            if ($type === 'field') {
                $fieldName = $change['data']['name'];
                $value = $change['data']['value'];

                if ($fieldName === 'name') {
                    $rule->setName($value);
                } else if ($fieldName === 'description') {
                    $rule->setDescription($value);
                } else if ($fieldName === 'status') {
                    $rule->setStatus($value);
                } else if ($fieldName === 'spamRatingFactor') {
                    $rule->setSpamRatingFactor(floatval($value));
                }

                $processed[] = [
                    'type' => 'field',
                    'fieldName' => $fieldName,
                ];
            } else if ($type === 'item') {
                $id = $change['data']['id'] ?? null;

                if (!$id) {
                    $item = $entityManager->getRepository(RuleItem::class)->findOneBy([
                        'uuid' => $change['data']['uuid'],
                    ]);

                    if (!$item) {
                        $item = (new RuleItem())
                            ->setRule($rule)
                            ->setUuid($change['data']['uuid']);

                        $newItems[] = $item;
                        $entityManager->persist($item);
                    }
                } else {
                    $item = $entityManager->find(RuleItem::class, $id);
                }

                $item
                    ->setType($change['data']['type'])
                    ->setValue($change['data']['value'])
                    ->setSpamRatingFactor(floatval($change['data']['spamRatingFactor'] ?? 1));

                $processed[] = [
                    'type' => 'item',
                    'uuid' => $item->getUuid(),
                ];
            }
        }

        $entityManager->flush();

        // Add the IDs of the newly saved items to add them to the row in the frontend.
        foreach ($newItems as $newItem) {
            foreach ($processed as $key => $processedData) {
                if ($processedData['uuid'] === $newItem->getUuid()) {
                    $entityManager->refresh($newItem);
                    $processed[$key]['id'] = $item->getId();
                }
            }
        }

        return new JsonResponse([
            'success' => true,
            'processed' => $processed,
        ]);
    }

    #[Route('/{id}/add-multiple', name: 'rule_edit_add_multiple')]
    public function addMultiple(Request $request, Rule $rule, EntityManagerInterface $entityManager): Response
    {
        $submittedToken = $request->request->get('token');
        if (!$this->isCsrfTokenValid('add-multiple-items', $submittedToken)) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'CSRF token invalid'], 401);
        }

        $items = $request->request->get('items');
        if (!$items) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Items missing'], 400);
        }

        $ruleType = $this->ruleTypeManager->getRuleType($rule->getType());
        $allowedTypes = array_map(function ($type) { return $type['key']; }, $ruleType->getSubtypes());

        $items = json_decode($request->request->get('items'), true);
        $types = [];
        $values = [];
        foreach ($items as $item) {
            $type = $item[0];
            if (!in_array($type, $allowedTypes)) {
                continue;
            }

            if (!in_array($type, $types)) {
                $types[] = $type;
            }

            $values[] = $item[1];
        }

        if (!$types || !$values) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'No types or no values found'], 400);
        }

        $sameType = (count($types) === 1);

        $qb = $entityManager->createQueryBuilder();
        $qb
            ->from('Mosparo\Entity\RuleItem', 'ri')
            ->andWhere('ri.rule = :rule')
            ->andWhere('ri.value IN (:values)')
            ->setParameter('rule', $rule)
            ->setParameter('values', $values, ArrayParameterType::STRING);

        if (!$sameType) {
            $qb
                ->select('ri.type, ri.value');

            $existingItems = $qb->getQuery()->getArrayResult();
        } else {
            $qb
                ->select('ri.value')
                ->andWhere('ri.type = :type')
                ->setParameter('type', current($types));

            $existingItems = $qb->getQuery()->getSingleColumnResult();
        }

        $counters = ['added' => 0, 'skipped' => 0, 'invalid' => 0];
        foreach ($items as $itemData) {
            $type = $itemData[0];
            if (!in_array($type, $allowedTypes)) {
                $counters['invalid']++;
                continue;
            }

            if ($existingItems) {
                if (!$sameType) {
                    $found = false;
                    foreach ($existingItems as $exItem) {
                        if ($exItem['type'] === $type && $exItem['value'] === $itemData[1]) {
                            $found = true;
                            break;
                        }
                    }

                    if ($found) {
                        $counters['skipped']++;
                        continue;
                    }
                } else {
                    if (in_array($itemData[1], $existingItems)) {
                        $counters['skipped']++;
                        continue;
                    }
                }
            }

            $item = new RuleItem();
            $item
                ->setRule($rule)
                ->setType($itemData[0])
                ->setValue($itemData[1])
                ->setSpamRatingFactor(floatval($itemData[2]));

            $entityManager->persist($item);
            $counters['added']++;
        }

        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'counters' => $counters,
        ]);
    }

    #[Route('/{id}/delete-selected', name: 'rule_edit_delete_selected')]
    public function deleteSelected(Request $request, Rule $rule, EntityManagerInterface $entityManager): Response
    {
        $submittedToken = $request->request->get('token');
        if (!$this->isCsrfTokenValid('delete-selected-items', $submittedToken)) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'CSRF token invalid'], 401);
        }

        $requestData = $request->request->all();
        $itemIds = $requestData['deleteItemIds'] ?? null;

        if ($itemIds === null) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Item IDs missing'], 400);
        }

        $qb = $entityManager->createQueryBuilder();
        $qb
            ->select('ri')
            ->from('Mosparo\Entity\RuleItem', 'ri')
            ->where('ri.rule = :rule')
            ->andWhere('ri.id IN (:ids)')
            ->setParameter('rule', $rule)
            ->setParameter('ids', $itemIds, ArrayParameterType::INTEGER);

        try {
            foreach ($qb->getQuery()->getResult() as $item) {
                $entityManager->remove($item);
            }

            $entityManager->flush();
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'errorMessage' => $e->getMessage(),
            ]);
        }

        return new JsonResponse([
            'success' => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'rule_delete')]
    public function delete(Request $request, Rule $rule, EntityManagerInterface $entityManager): Response
    {
        if ($request->request->has('delete-token')) {
            $submittedToken = $request->request->get('delete-token');

            if ($this->isCsrfTokenValid('delete-rule', $submittedToken)) {
                // Using the query for maximum performance. Especially important for rules with thousands
                // of rule items.
                $qb = $entityManager->createQueryBuilder();
                $qb
                    ->delete('Mosparo\Entity\RuleItem', 'ri')
                    ->andWhere('ri.rule = :rule')
                    ->setParameter('rule', $rule)
                    ->getQuery()
                    ->execute();

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

    #[Route('/{id}/export-items', name: 'rule_export_items')]
    public function exportItems(Request $request, Rule $rule, EntityManagerInterface $entityManager): Response
    {
        $qb = $entityManager->createQueryBuilder();
        $qb
            ->from('Mosparo\Entity\RuleItem', 'ri')
            ->andWhere('ri.rule = :rule')
            ->setParameter('rule', $rule);

        $qbCount = (clone $qb)
            ->select('COUNT(ri.id)')
        ;
        $numberOfItemsPerIteration = 1000;
        $numberOfItems = $qbCount->getQuery()->getSingleScalarResult();
        $numberOfIterations = ceil($numberOfItems / $numberOfItemsPerIteration);

        $qb
            ->select('ri')
            ->setMaxResults($numberOfItemsPerIteration)
        ;

        $response = new StreamedResponse();
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->setCallback(function () use ($entityManager, $qb, $numberOfIterations, $numberOfItemsPerIteration): void {
            $handle = fopen("php://output", 'w');
            fputcsv($handle, ['Type', 'Value', 'Rating']);

            for ($idx = 0; $idx < $numberOfIterations; $idx++) {
                $qbi = clone $qb;
                $qbi
                    ->setFirstResult($idx * $numberOfItemsPerIteration)
                ;

                foreach ($qbi->getQuery()->getResult() as $ruleItem) {
                    fputcsv($handle, [$ruleItem->getType(), $ruleItem->getValue(), $ruleItem->getSpamRatingFactor()]);

                    $entityManager->detach($ruleItem);
                }

                flush();
            }

            fclose($handle);

            flush();
        });

        return $response;
    }

    public function buildFrontendChoices(array $subtypes): array
    {
        $choices = [];
        foreach ($subtypes as $subtype) {
            $choices[$subtype['key']] = $this->translator->trans($subtype['name'], [], 'mosparo');
        }

        return $choices;
    }

    protected function getValueOptions(Request $request, RuleTypeInterface $ruleType)
    {
        $valueOptions = [];

        if ($ruleType instanceof UnicodeBlockRuleType) {
            $valueOptions = $ruleType->getValueOptions($request->getLocale());
        }

        return $valueOptions;
    }

    public function findMatchingValues(array $options, $pattern)
    {
        $matchingOptions = [];
        $pattern = Pattern::create(strtolower($pattern));
        foreach ($options as $key => $label) {
            if ($pattern->match(strtolower($label))) {
                $matchingOptions[] = $key;
            }
        }

        return $matchingOptions;
    }
}
