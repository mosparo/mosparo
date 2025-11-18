<?php

namespace Mosparo\Controller\ProjectRelated;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Mosparo\DataTable\MosparoDataTableFactory;
use Mosparo\Entity\RulePackage;
use Mosparo\Entity\RulePackageRuleCache;
use Mosparo\Entity\RulePackageRuleItemCache;
use Mosparo\Enum\RulePackageType;
use Mosparo\Enum\RulePackageTypeCategory;
use Mosparo\Exception;
use Mosparo\Form\RulePackageFormType;
use Mosparo\Helper\RulePackageHelper;
use Mosparo\Rule\RuleTypeManager;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\NumberColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/project/{_projectId}/rule-packages')]
class RulePackageController extends AbstractController implements ProjectRelatedInterface
{
    use ProjectRelatedTrait;

    protected EntityManagerInterface $entityManager;

    protected MosparoDataTableFactory $dataTableFactory;

    protected RuleTypeManager $ruleTypeManager;

    protected RulePackageHelper $rulePackageHelper;

    protected TranslatorInterface $translator;

    public function __construct(
        EntityManagerInterface $entityManager,
        MosparoDataTableFactory $dataTableFactory,
        RuleTypeManager $ruleTypeManager,
        RulePackageHelper $rulePackageHelper,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->dataTableFactory = $dataTableFactory;
        $this->ruleTypeManager = $ruleTypeManager;
        $this->rulePackageHelper = $rulePackageHelper;
        $this->translator = $translator;
    }

    #[Route('/', name: 'rule_package_list')]
    public function index(Request $request): Response
    {
        $table = $this->dataTableFactory->create(['autoWidth' => true])
            ->add('name', TextColumn::class, ['label' => 'rulePackage.list.name'])
            ->add('type', TwigColumn::class, [
                'label' => 'rulePackage.list.type',
                'template' => 'project_related/rule_package/list/_type.html.twig',
            ])
            ->add('status', TwigColumn::class, [
                'label' => 'rulePackage.list.status',
                'template' => 'project_related/rule_package/list/_status.html.twig',
            ])
            ->add('refreshedAt', TwigColumn::class, [
                'label' => 'rulePackage.list.refreshedAt',
                'propertyPath' => 'rulePackageCache.refreshedAt',
                'template' => 'project_related/rule_package/list/_date.html.twig',
            ])
            ->add('updatedAt', TwigColumn::class, [
                'label' => 'rulePackage.list.updatedAt',
                'propertyPath' => 'rulePackageCache.updatedAt',
                'template' => 'project_related/rule_package/list/_date.html.twig',
            ])
            ->add('actions', TwigColumn::class, [
                'label' => 'rulePackage.list.actions',
                'className' => 'buttons',
                'template' => 'project_related/rule_package/list/_actions.html.twig'
            ])
            ->createAdapter(ORMAdapter::class, [
                'entity' => RulePackage::class,
                'query' => function (QueryBuilder $builder) {
                    $builder
                        ->select('e')
                        ->from(RulePackage::class, 'e');
                },
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('project_related/rule_package/list.html.twig', [
            'datatable' => $table
        ]);
    }

    #[Route('/add/choose-type', name: 'rule_package_add_choose_type')]
    public function addChooseType(): Response
    {
        return $this->render('project_related/rule_package/add_choose_type.html.twig', [
            'categories' => RulePackageTypeCategory::list(),
        ]);
    }

    #[Route('/add/with-type/{type}', name: 'rule_package_add_with_type')]
    public function addWithType(Request $request, string $type = null): Response
    {
        return $this->form($request, null, $type);
    }

    #[Route('/{id}/edit', name: 'rule_package_edit')]
    public function form(Request $request, RulePackage $rulePackage = null, ?string $type = null): Response
    {
        $isNew = false;
        if ($rulePackage === null) {
            $rulePackageType = RulePackageType::fromKey($type);
            if ($rulePackageType === null) {
                return $this->redirectToRoute('rule_package_add_choose_type', ['_projectId' => $this->getActiveProject()->getId()]);
            }

            $rulePackage = (new RulePackage())
                ->setType($rulePackageType)
                ->setStatus(true);
            $isNew = true;
        }

        $form = $this->createForm(RulePackageFormType::class, $rulePackage);
        $form->handleRequest($request);

        $hasError = false;
        $errorMessage = '';
        if ($form->isSubmitted() && $form->isValid()) {
            if ($isNew) {
                $this->entityManager->persist($rulePackage);
            }

            if (in_array($rulePackage->getType(), RulePackageType::automaticTypes())) {
                try {
                    $this->rulePackageHelper->fetchRulePackage($rulePackage);
                } catch (Exception $e) {
                    $hasError = true;
                    $errorMessage = $e->getMessage();
                }
            }

            if (!$hasError) {
                $this->entityManager->flush();

                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'success',
                    $this->translator->trans(
                        'rulePackage.form.message.successfullySaved',
                        [],
                        'mosparo'
                    )
                );

                return $this->redirectToRoute('rule_package_list', ['_projectId' => $this->getActiveProject()->getId()]);
            }
        }

        return $this->render('project_related/rule_package/form.html.twig', [
            'rulePackage' => $rulePackage,
            'form' => $form->createView(),
            'isNew' => $isNew,
            'hasError' => $hasError,
            'errorMessage' => $errorMessage
        ]);
    }

    #[Route('/{id}/delete', name: 'rule_package_delete')]
    public function delete(Request $request, RulePackage $rulePackage): Response
    {
        if ($request->request->has('delete-token')) {
            $submittedToken = $request->request->get('delete-token');

            if ($this->isCsrfTokenValid('delete-rule-package', $submittedToken)) {
                $this->entityManager->remove($rulePackage);
                $this->entityManager->flush();

                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'success',
                    $this->translator->trans(
                        'rulePackage.delete.message.successfullyDeleted',
                        ['%rulePackageName%' => $rulePackage->getName()],
                        'mosparo'
                    )
                );

                return $this->redirectToRoute('rule_package_list', ['_projectId' => $this->getActiveProject()->getId()]);
            }
        }

        return $this->render('project_related/rule_package/delete.html.twig', [
            'rulePackage' => $rulePackage,
        ]);
    }

    #[Route('/{id}/view', name: 'rule_package_view')]
    #[Route('/{id}/view/filter/{filter}', name: 'rule_package_view_filtered')]
    public function view(Request $request, RulePackage $rulePackage, $filter = ''): Response
    {
        $hasError = false;
        $errorMessage = '';
        if (in_array($rulePackage->getType(), RulePackageType::automaticTypes())) {
            try {
                $result = $this->rulePackageHelper->fetchRulePackage($rulePackage);

                if ($result) {
                    $this->entityManager->flush();
                }
            } catch (\Exception $e) {
                $hasError = true;
                $errorMessage = $e->getMessage();
            }
        }

        $filteredType = null;
        if (in_array($filter, $this->ruleTypeManager->getRuleTypeKeys())) {
            $filteredType = $filter;
        }

        $table = $this->dataTableFactory->create(['autoWidth' => true])
            ->add('name', TextColumn::class, ['label' => 'rulePackage.view.list.rules.name'])
            ->add('type', TwigColumn::class, [
                'label' => 'rulePackage.view.list.rules.type',
                'template' => 'project_related/rule_package/view/rule_list/_type.html.twig'
            ])
            ->add('numberOfRuleItems', TwigColumn::class, [
                'label' => 'rulePackage.view.list.rules.numberOfRuleItems',
                'template' => 'project_related/rule_package/view/rule_list/_numberOfRuleItems.html.twig'
            ])
            ->add('spamRatingFactor', NumberColumn::class, ['label' => 'rulePackage.view.list.rules.spamRatingFactor'])
            ->add('actions', TwigColumn::class, [
                'label' => 'rulePackage.view.list.rules.actions',
                'className' => 'buttons',
                'template' => 'project_related/rule_package/view/rule_list/_actions.html.twig'
            ])
            ->createAdapter(ORMAdapter::class, [
                'entity' => RulePackageRuleCache::class,
                'query' => function (QueryBuilder $builder) use ($rulePackage, $filteredType) {
                    $builder
                        ->select('e')
                        ->from(RulePackageRuleCache::class, 'e')
                        ->where('e.rulePackageCache = :rulePackageCache')
                        ->setParameter('rulePackageCache', $rulePackage->getRulePackageCache());

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
            ->from(RulePackageRuleCache::class, 'rrc')
            ->where('rrc.rulePackageCache = :rulePackageCache')
            ->groupBy('rrc.type')
            ->setParameter('rulePackageCache', $rulePackage->getRulePackageCache());
        $numberOfRulesByType = [];
        foreach ($qb->getQuery()->getArrayResult() as $result) {
            $numberOfRulesByType[$result['type']] = $result['countRules'];
        }

        return $this->render('project_related/rule_package/view.html.twig', [
            'rulePackage' => $rulePackage,
            'hasError' => $hasError,
            'errorMessage' => $errorMessage,
            'datatable' => $table,
            'ruleTypes' => $this->ruleTypeManager->getRuleTypes(),
            'numberOfRulesByType' => $numberOfRulesByType,
            'filter' => $filter,
        ]);
    }

    #[Route('/{id}/view/rule/{ruleUuid}', name: 'rule_package_view_rule')]
    public function viewRule(Request $request, RulePackage $rulePackage, string $ruleUuid): Response
    {
        $rulePackageRuleCacheRepository = $this->entityManager->getRepository(RulePackageRuleCache::class);
        $rulePackageRuleCache = $rulePackageRuleCacheRepository->findOneBy(['uuid' => $ruleUuid]);

        if (!$rulePackageRuleCache) {
            return $this->redirectToRoute('rule_package_view', ['_projectId' => $this->getActiveProject()->getId(), 'id' => $rulePackage->getId()]);
        }

        $table = $this->dataTableFactory->create(['autoWidth' => true])
            ->add('type', TwigColumn::class, [
                'label' => 'rulePackage.view.list.ruleItems.type',
                'template' => 'project_related/rule_package/view/rule_item_list/_type.html.twig'
            ])
            ->add('value', TwigColumn::class, [
                'label' => 'rulePackage.view.list.ruleItems.value',
                'template' => 'project_related/rule_package/view/rule_item_list/_value.html.twig'
            ])
            ->add('spamRatingFactor', NumberColumn::class, ['label' => 'rulePackage.view.list.ruleItems.spamRatingFactor'])
            ->createAdapter(ORMAdapter::class, [
                'entity' => RulePackageRuleItemCache::class,
                'query' => function (QueryBuilder $builder) use ($rulePackageRuleCache) {
                    $builder
                        ->select('e')
                        ->from(RulePackageRuleItemCache::class, 'e')
                        ->where('e.rulePackageRuleCache = :rulePackageRuleCache')
                        ->setParameter('rulePackageRuleCache', $rulePackageRuleCache);
                },
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('project_related/rule_package/view_rule.html.twig', [
            'rulePackage' => $rulePackage,
            'rule' => $rulePackageRuleCache,
            'datatable' => $table
        ]);
    }
}
