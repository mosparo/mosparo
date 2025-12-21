<?php

namespace Mosparo\Controller\ProjectRelated;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Mosparo\DataTable\MosparoDataTableFactory;
use Mosparo\Entity\RulePackage;
use Mosparo\Entity\RulePackageProcessingJob;
use Mosparo\Entity\RulePackageRuleCache;
use Mosparo\Entity\RulePackageRuleItemCache;
use Mosparo\Enum\ProcessingJobType;
use Mosparo\Enum\RulePackageResult;
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
use Symfony\Component\HttpFoundation\JsonResponse;
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
            'datatable' => $table,
            'hasRulePackages' => $this->rulePackageHelper->hasRulePackages(),
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

                if (in_array($rulePackage->getType(), RulePackageType::automaticTypes())) {
                    return $this->redirectToRoute('rule_package_update_cache', ['_projectId' => $this->getActiveProject()->getId(), 'id' => $rulePackage->getId()]);
                } else {
                    return $this->redirectToRoute('rule_package_list', ['_projectId' => $this->getActiveProject()->getId()]);
                }
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
                $processingJob = (new RulePackageProcessingJob())
                    ->setRulePackage($rulePackage)
                    ->setType(ProcessingJobType::DELETE_RULE_PACKAGE)
                    ->setProject($rulePackage->getProject())
                ;

                $this->entityManager->persist($processingJob);
                $this->entityManager->flush();

                return $this->redirectToRoute('rule_package_delete_process', ['id' => $processingJob->getId(), '_projectId' => $this->getActiveProject()->getId()]);
            }
        }

        return $this->render('project_related/rule_package/delete.html.twig', [
            'rulePackage' => $rulePackage,
        ]);
    }

    #[Route('/job/{id}/delete/process', name: 'rule_package_delete_process')]
    public function deleteRulePackageProcess(RulePackageProcessingJob $processingJob): Response
    {
        return $this->render('project_related/rule_package/delete-process.html.twig', [
            'processingJob' => $processingJob,
        ]);
    }

    #[Route('/job/{id}/delete/process/execute', name: 'rule_package_delete_process_execute')]
    public function deleteRulePackageProcessExecute(RulePackageProcessingJob $processingJob): Response
    {
        $response = new JsonResponse();

        $error = false;
        $errorMessage = null;
        $cleanupProgress = null;
        try {
            $result = $this->rulePackageHelper->cleanupForRulePackage(
                $processingJob,
                $this->rulePackageHelper->getCacheDirectory($processingJob->getRulePackage()->getId()),
                false,
                time(),
                1
            );

            if ($result === RulePackageResult::COMPLETED) {
                $this->entityManager->remove($processingJob->getRulePackage());
                $this->entityManager->flush();

                $cleanupProgress = 100;
            } else {
                $this->entityManager->flush();

                $cleanupProgress = $processingJob->getCleanupTasks() ? ($processingJob->getProcessedCleanupTasks() / $processingJob->getCleanupTasks()) * 100 : 0;
            }
        } catch (\Exception $e) {
            $result = RulePackageResult::UNKNOWN_ERROR;
            $error = true;
            $errorMessage = $e->getMessage();
        }

        $completed = false;
        if ($result !== RulePackageResult::UNFINISHED) {
            $completed = true;
        }

        $response->setData([
            'result' => $result,
            'completed' => $completed,
            'error' => $error,
            'errorMessage' => $errorMessage,
            'cleanupProgress' => round($cleanupProgress, 2),
        ]);

        return $response;
    }

    #[Route('/update-cache', name: 'rule_package_update_cache_all')]
    #[Route('/{id}/update-cache', name: 'rule_package_update_cache')]
    public function updateCache(?RulePackage $rulePackage = null): Response
    {
        if (!$this->rulePackageHelper->hasRulePackages()) {
            return $this->redirectToRoute('rule_package_list', ['_projectId' => $this->getActiveProject()->getId()]);
        }

        if ($rulePackage) {
            $rulePackages = [$rulePackage];
        } else {
            $rulePackages = $this->entityManager->getRepository(RulePackage::class)->findBy([
                'type' => [RulePackageType::AUTOMATICALLY_FROM_URL, RulePackageType::AUTOMATICALLY_FROM_FILE],
            ]);
        }

        $rulePackageUrls = [];
        foreach ($rulePackages as $rulePackage) {
            $rulePackageUrls[$rulePackage->getId()] = $this->generateUrl('rule_package_update_cache_execute', [
                '_projectId' => $rulePackage->getProject()->getId(),
                'id' => $rulePackage->getId(),
            ]);
        }

        return $this->render('project_related/rule_package/update-cache.html.twig', [
            'rulePackages' => $rulePackages,
            'rulePackageUrls' => $rulePackageUrls,
        ]);
    }

    #[Route('/{id}/update-cache/execute', name: 'rule_package_update_cache_execute')]
    public function updateCacheExecute(Request $request, RulePackage $rulePackage): Response
    {
        $response = new JsonResponse();

        $pj = $rulePackage->getFirstProcessingJob(ProcessingJobType::UPDATE_CACHE);
        $error = false;
        $errorMessage = null;
        $importProgress = null;
        $cleanupProgress = null;
        try {
            $result = $this->rulePackageHelper->fetchRulePackage($rulePackage, time(), 1); // Short timeouts lead to faster updates in the UI

            if (!$pj) {
                $pj = $rulePackage->getFirstProcessingJob(ProcessingJobType::UPDATE_CACHE);
            }

            if (in_array($result, [RulePackageResult::COMPLETED, RulePackageResult::ALREADY_UP_TO_DATE])) {
                $importProgress = 100;
                $cleanupProgress = 100;
            } else if ($pj) {
                $importProgress = $pj->getImportTasks() ? ($pj->getProcessedImportTasks() / $pj->getImportTasks()) * 100 : 0;
                $cleanupProgress = $pj->getCleanupTasks() ? ($pj->getProcessedCleanupTasks() / $pj->getCleanupTasks()) * 100 : 0;
            }
        } catch (\Exception $e) {
            $result = RulePackageResult::UNKNOWN_ERROR;
            $error = true;
            $errorMessage = $e->getMessage();
        }

        if ($result === RulePackageResult::UNKNOWN_ERROR) {
            if ($pj) {
                $this->entityManager->remove($pj);
                $this->entityManager->flush();
            }

            $this->rulePackageHelper->deleteCacheDirectory($rulePackage->getId());
        }

        $completed = false;
        if ($result !== RulePackageResult::UNFINISHED) {
            $completed = true;
        }

        $response->setData([
            'result' => $result,
            'completed' => $completed,
            'error' => $error,
            'errorMessage' => $errorMessage,
            'importCompleted' => ($importProgress === 100),
            'importProgress' => round($importProgress, 2),
            'cleanupProgress' => round($cleanupProgress, 2),
        ]);

        return $response;
    }

    #[Route('/{id}/view', name: 'rule_package_view')]
    #[Route('/{id}/view/filter/{filter}', name: 'rule_package_view_filtered')]
    public function view(Request $request, RulePackage $rulePackage, $filter = ''): Response
    {
        $hasError = false;
        $errorMessage = '';

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
