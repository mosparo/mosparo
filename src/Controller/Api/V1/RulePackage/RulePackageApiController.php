<?php

namespace Mosparo\Controller\Api\V1\RulePackage;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Mosparo\Entity\RulePackage;
use Mosparo\Entity\RulePackageCache;
use Mosparo\Entity\RulePackageRuleCache;
use Mosparo\Entity\RulePackageRuleItemCache;
use Mosparo\Enum\RulePackageType;
use Mosparo\Helper\ProjectHelper;
use Mosparo\Helper\RulePackageHelper;
use Mosparo\Repository\RulePackageRepository;
use Mosparo\Specifications\Specifications;
use Opis\JsonSchema\Helper;
use Opis\JsonSchema\Validator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/rule-package')]
class RulePackageApiController extends AbstractController
{
    protected ProjectHelper $projectHelper;

    protected RulePackageHelper $rulePackageHelper;

    protected EntityManagerInterface $entityManager;

    public function __construct(ProjectHelper $projectHelper, RulePackageHelper $rulePackageHelper, EntityManagerInterface $entityManager)
    {
        $this->projectHelper = $projectHelper;
        $this->rulePackageHelper = $rulePackageHelper;
        $this->entityManager = $entityManager;
    }

    #[Route('/{id}/hash-index', name: 'rule_package_api_hash_index', methods: ['GET'])]
    public function hashIndex(Request $request, RulePackage $rulePackage, LoggerInterface $logger): Response
    {
        $rulePackageCache = $rulePackage->getRulePackageCache();
        if (!$rulePackageCache) {
            return new JsonResponse([
                'result' => false,
                'noCache' => true,
            ], 205);
        }

        return new StreamedResponse(function () use ($request, $rulePackageCache, $logger) {
            if ($logger instanceof Logger) {
                $logger->pushHandler(new NullHandler());
            }

            // Remove the middlewares to prevent memory issues, especially in DEV environment.
            $this->entityManager->getConnection()->getConfiguration()->getMiddlewares();
            $this->entityManager->getConnection()->getConfiguration()->setMiddlewares([]);

            $sentContent = false;
            $offset = intval($request->query->get('offset', 0));
            $maxEntities = intval($request->query->get('maxItems', 100000));

            $qb = $this->entityManager->createQueryBuilder()
                ->select('rprc')
                ->from(RulePackageRuleCache::class, 'rprc')
                ->where('rprc.rulePackageCache = :rpc')
                ->setParameter('rpc', $rulePackageCache)
            ;
            $rulePackageRuleIds = [];
            $counter = 0;
            foreach ($qb->getQuery()->toIterable() as $rule) {
                echo sprintf('%s::r/%s/%d', $rule->getUuid(), $rule->getHash(), $rule->getId()) . PHP_EOL;

                $rulePackageRuleIds[] = $rule->getId();

                $this->entityManager->detach($rule);
                unset($rule);

                $counter++;
                if ($counter % 1000 === 0) {
                    ob_flush();
                    flush();
                }
            }

            ob_flush();
            flush();

            $hasMore = true;
            $startPos = $offset;
            $maxItems = 100000;

            while ($hasMore) {
                $qb = $this->entityManager->createQueryBuilder()
                    ->select('rpric')
                    ->from(RulePackageRuleItemCache::class, 'rpric')
                    ->where('rpric.rulePackageRuleCache IN (:rprc)')
                    ->setParameter('rprc', $rulePackageRuleIds, ArrayParameterType::INTEGER)
                    ->setFirstResult($startPos)
                    ->setMaxResults($maxItems)
                    ->orderBy('rpric.id')
                ;
                $counter = 0;
                foreach ($qb->getQuery()->toIterable() as $ruleItem) {
                    echo sprintf('%s::i/%s/%d', $ruleItem->getUuid(), $ruleItem->getHash(), $ruleItem->getId()) . PHP_EOL;

                    $this->entityManager->detach($ruleItem);
                    unset($ruleItem);

                    $counter++;
                    if ($counter % 1000 === 0) {
                        ob_flush();
                        flush();
                    }
                }

                if ($counter > 0) {
                    $sentContent = true;
                }

                $startPos += $maxItems;

                if ($startPos >= $offset + $maxEntities || $counter === 0) {
                    $hasMore = false;
                }

                ob_flush();
                flush();
            }

            if (!$sentContent) {
                echo '###END';
            }
        });
    }

    #[Route('/{id}/rules', name: 'rule_package_api_rules', methods: ['GET'])]
    public function rules(Request $request, RulePackage $rulePackage): Response
    {
        $rulePackageCache = $rulePackage->getRulePackageCache();
        if (!$rulePackageCache) {
            return new JsonResponse([
                'result' => false,
                'noCache' => true,
            ]);
        }

        $totalRules = $rulePackageCache->getNumberOfRules() ?? $this->rulePackageHelper->countRulesForRulePackage($rulePackageCache);
        $page = $request->get('page', 1);
        $perPage = $request->get('perPage', 1000);
        $totalPages = ceil($totalRules / $perPage);

        $qb = $this->entityManager->createQueryBuilder()
            ->select('rprc')
            ->from(RulePackageRuleCache::class, 'rprc')
            ->where('rprc.rulePackageCache = :rpc')
            ->andWhere('rprc.project = :project')
            ->setParameter('rpc', $rulePackageCache)
            ->setParameter('project', $rulePackageCache->getProject())
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
        ;

        $rules = [];
        foreach ($qb->getQuery()->toIterable() as $rule) {
            $rules[] = [
                'id' => $rule->getId(),
                'uuid' => $rule->getUuid(),
                'type' => $rule->getType(),
                'name' => $rule->getName(),
                'description' => $rule->getDescription(),
                'numberOfItems' => $rule->getNumberOfItems(),
                'spamRatingFactor' => $rule->getSpamRatingFactor(),
                'updatedAt' => $rule->getUpdatedAt()->format(\DateTimeInterface::ATOM),
                'listRoute' => $this->generateUrl('rule_package_api_rule_items', [
                    'rulePackage' => $rulePackage->getId(),
                    'rulePackageRuleCache' => $rule->getId(),
                ]),
            ];
        }

        return new JsonResponse([
            'result' => true,
            'rules' => $rules,
            'page' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/{rulePackage}/rules/{rulePackageRuleCache}/rule-items', name: 'rule_package_api_rule_items', methods: ['GET'])]
    public function ruleItems(Request $request, RulePackage $rulePackage, RulePackageRuleCache $rulePackageRuleCache): Response
    {
        $rulePackageCache = $rulePackage->getRulePackageCache();
        if (!$rulePackageCache) {
            return new JsonResponse([
                'result' => false,
                'noCache' => true,
            ]);
        }

        $totalRuleItems = $rulePackageRuleCache->getNumberOfItems() ?? $this->rulePackageHelper->countRuleItemsForRule($rulePackageRuleCache);
        $page = $request->get('page', 1);
        $perPage = $request->get('perPage', 1000);
        $totalPages = ceil($totalRuleItems / $perPage);

        $qb = $this->entityManager->createQueryBuilder()
            ->select('rpric')
            ->from(RulePackageRuleItemCache::class, 'rpric')
            ->where('rpric.rulePackageRuleCache = :rprc')
            ->andWhere('rpric.project = :project')
            ->setParameter('rprc', $rulePackageRuleCache)
            ->setParameter('project', $rulePackageCache->getProject())
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
        ;

        $ruleItems = [];
        foreach ($qb->getQuery()->toIterable() as $item) {
            $ruleItems[] = [
                'id' => $item->getId(),
                'uuid' => $item->getUuid(),
                'type' => $item->getType(),
                'value' => $item->getValue(),
                'rating' => $item->getSpamRatingFactor(),
            ];
        }

        return new JsonResponse([
            'result' => true,
            'rules' => $ruleItems,
            'page' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/{id}/batch', name: 'rule_package_api_batch', methods: ['POST'])]
    public function batch(Request $request, RulePackage $rulePackage): Response
    {
        $tasks = $request->get('tasks');
        if (!$tasks) {
            return new JsonResponse([
                'result' => false,
                'error' => true,
                'errorMessage' => 'Tasks undefined.',
            ], 400);
        }

        $validator = new Validator();
        $validator->resolver()->registerFile('http://schema.mosparo.io/rule-package-batch-urp.json', Specifications::getJsonSchemaPath(Specifications::JSON_SCHEMA_RULE_PACKAGE_BATCH_URP));
        $validator->resolver()->registerFile('http://schema.mosparo.io/rule-package-batch-ur.json', Specifications::getJsonSchemaPath(Specifications::JSON_SCHEMA_RULE_PACKAGE_BATCH_UR));
        $validator->resolver()->registerFile('http://schema.mosparo.io/rule-package-batch-uri.json', Specifications::getJsonSchemaPath(Specifications::JSON_SCHEMA_RULE_PACKAGE_BATCH_URI));
        $validator->resolver()->registerFile('http://schema.mosparo.io/rule-package-batch-remove.json', Specifications::getJsonSchemaPath(Specifications::JSON_SCHEMA_RULE_PACKAGE_BATCH_REMOVE));

        $rulePackageCache = $rulePackage->getRulePackageCache();
        if (!$rulePackageCache) {
            $rulePackageCache = new RulePackageCache();
            $rulePackageCache->setRulePackage($rulePackage);

            $this->entityManager->persist($rulePackageCache);
            $this->entityManager->flush();
        }

        $errors = [];
        $processedRules = [];
        $storableRuleItems = [];
        $storableRuleItemRuleUuids = [];
        foreach ($tasks as $task) {
            $type = $task['type'] ?? null;
            if ($type === 'update_rule_package') {
                $rulePackageData = $task['data'];

                $validationResult = $validator->validate(Helper::toJSON($rulePackageData), 'http://schema.mosparo.io/rule-package-batch-urp.json');
                if (!$validationResult->isValid()) {
                    $errors[] = sprintf('Task data not valid: %s; Data: %s', $type, json_encode($task['data']));
                    continue;
                }

                $rulePackageCache->setUpdatedAt(\DateTime::createFromFormat(\DateTimeInterface::ATOM, $rulePackageData['lastUpdatedAt']));
                $rulePackageCache->setRefreshInterval($rulePackageData['refreshInterval']);
            } else if ($type === 'store_rule') {
                $ruleData = $task['data'];

                $validationResult = $validator->validate(Helper::toJSON($ruleData), 'http://schema.mosparo.io/rule-package-batch-ur.json');
                if (!$validationResult->isValid()) {
                    $errors[] = sprintf('Task data not valid: %s; Data: %s', $type, json_encode($task['data']));
                    continue;
                }

                $rule = $this->findRulePackageRuleCache($rulePackageCache, $ruleData['uuid']);
                if (!$rule) {
                    $rule = new RulePackageRuleCache();
                    $rule->setUuid($ruleData['uuid']);
                    $rule->setType($ruleData['type']);
                    $rule->setRulePackageCache($rulePackageCache);
                    $rule->setProject($rulePackage->getProject());

                    $this->entityManager->persist($rule);

                    $processedRules[$rule->getUuid()] = $rule;
                }

                $rule->setType($ruleData['type']);
                $rule->setName($ruleData['name']);
                $rule->setDescription($ruleData['description'] ?? null);
                $rule->setSpamRatingFactor($ruleData['spamRatingFactor'] ?? null);
                $rule->setUpdatedAt($rulePackageCache->getUpdatedAt());
            } else if ($type === 'store_rule_item') {
                $ruleItemData = $task['data'];

                $validationResult = $validator->validate(Helper::toJSON($ruleItemData), 'http://schema.mosparo.io/rule-package-batch-uri.json');
                if (!$validationResult->isValid()) {
                    $errors[] = sprintf('Task data not valid: %s; Data: %s', $type, json_encode($task['data']));
                    continue;
                }

                $storableRuleItems[$ruleItemData['uuid']] = $ruleItemData;

                if (!in_array($ruleItemData['ruleUuid'], $storableRuleItemRuleUuids)) {
                    $storableRuleItemRuleUuids[] = $ruleItemData['ruleUuid'];
                }
            } else if ($type === 'remove_rule') {
                $ruleData = $task['data'];

                $validationResult = $validator->validate(Helper::toJSON($ruleData), 'http://schema.mosparo.io/rule-package-batch-remove.json');
                if (!$validationResult->isValid()) {
                    $errors[] = sprintf('Task data not valid: %s; Data: %s', $type, json_encode($task['data']));
                    continue;
                }

                $qb = $this->entityManager->createQueryBuilder()
                    ->delete(RulePackageRuleCache::class, 'rprc')
                    ->where('rprc.id = :id')
                    ->andWhere('rprc.project = :project')
                    ->setParameter('project', $rulePackageCache->getProject())
                    ->setParameter('id', $ruleData['id'])
                ;

                try {
                    $qb->getQuery()->execute();
                } catch (\Exception $e) {
                    $errors[] = sprintf('Cannot delete %s; Error: %s', $ruleItemData['uuid'], $e->getMessage());
                }
            } else if ($type === 'remove_rule_item') {
                $ruleItemData = $task['data'];

                $validationResult = $validator->validate(Helper::toJSON($ruleItemData), 'http://schema.mosparo.io/rule-package-batch-remove.json');
                if (!$validationResult->isValid()) {
                    $errors[] = sprintf('Task data not valid: %s; Data: %s', $type, json_encode($task['data']));
                    continue;
                }

                $qb = $this->entityManager->createQueryBuilder()
                    ->delete(RulePackageRuleItemCache::class, 'rpric')
                    ->where('rpric.id = :id')
                    ->andWhere('rpric.project = :project')
                    ->setParameter('project', $rulePackageCache->getProject())
                    ->setParameter('id', $ruleItemData['id'])
                ;

                try {
                    $qb->getQuery()->execute();
                } catch (\Exception $e) {
                    $errors[] = sprintf('Cannot delete %s; Error: %s', $ruleItemData['uuid'], $e->getMessage());
                }
            }
        }

        // Store the rule package and rule changes to the database
        $this->entityManager->flush();

        // To reduce the load at the database, we store all rule items at once.
        if ($storableRuleItems) {
            $ruleObjects = $this->rulePackageHelper->findRuleObjects($rulePackageCache, $storableRuleItemRuleUuids);
            $itemObjects = $this->rulePackageHelper->findRuleItemObjects($ruleObjects, array_keys($storableRuleItems));

            foreach ($storableRuleItems as $ruleItemData) {
                $ruleItem = $itemObjects[$ruleItemData['uuid']] ?? null;
                if (!$ruleItem) {
                    $rule = $ruleObjects[$ruleItemData['ruleUuid']] ?? null;
                    if (!$rule) {
                        $errorMsg = sprintf('Rule not available: %s', $ruleItemData['ruleUuid']);

                        if (!in_array($errorMsg, $errors)) {
                            $errors[] = $errorMsg;
                        }
                        continue;
                    }

                    $ruleItem = new RulePackageRuleItemCache();
                    $ruleItem->setRulePackageRuleCache($rule);
                    $ruleItem->setUuid($ruleItemData['uuid']);
                    $ruleItem->setProject($rulePackage->getProject());

                    $this->entityManager->persist($ruleItem);
                }

                $ruleItem->setType($ruleItemData['type']);
                $ruleItem->setValue($ruleItemData['value']);
                $ruleItem->setSpamRatingFactor($ruleItemData['rating'] ?? null);
                $ruleItem->setUpdatedAt($rulePackageCache->getUpdatedAt());
            }

            foreach ($ruleObjects as $rule) {
                $rule
                    ->setUpdatedAt($rulePackageCache->getUpdatedAt())
                    ->setNumberOfItems(null);
            }
        }

        $rulePackageCache->setRefreshedAt(new \DateTime());

        $this->entityManager->flush();

        return new JsonResponse([
            'result' => true,
            'errors' => $errors,
        ]);
    }

    #[Route('/import', name: 'rule_package_api_import', methods: ['POST'])]
    public function import(Request $request, EntityManagerInterface $entityManager, RulePackageRepository $rulePackageRepository): Response
    {
        // If there is no active project, we cannot do anything.
        if (!$this->projectHelper->hasActiveProject()) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'No project available.']);
        }

        $activeProject = $this->projectHelper->getActiveProject();

        if (!$request->request->has('rulePackageId') || !$request->request->has('rulePackageContent')) {
            // Prepare the API debug data
            $debugInformation = [];
            if ($activeProject->isApiDebugMode()) {
                $debugInformation['debugInformation'] = [
                    'reason' => 'required_parameter_missing',
                    'hasRulePackageId' => $request->request->has('rulePackageId'),
                    'hasRulePackageContent' => $request->request->has('rulePackageContent'),
                ];
            }

            return new JsonResponse(['error' => true, 'errorMessage' => 'Required parameter missing.'] + $debugInformation);
        }

        $rulePackage = $rulePackageRepository->find($request->request->get('rulePackageId'));
        if ($rulePackage === null) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Rule package not found.']);
        }

        if ($rulePackage->getType() !== RulePackageType::MANUALLY_VIA_API) {
            // Prepare the API debug data
            $debugInformation = [];
            if ($activeProject->isApiDebugMode()) {
                $debugInformation['debugInformation'] = [
                    'reason' => 'rule_package_type_invalid',
                    'expectedType' => RulePackageType::MANUALLY_VIA_API->name,
                    'receivedType' => $rulePackage->geTType()->name
                ];
            }

            return new JsonResponse([
                'error' => true,
                'errorMessage' => sprintf('Rule package type (%s) is not allowed.', $rulePackage->getType()->name)
            ] + $debugInformation);
        }

        $verifiedHash = false;
        $rulePackageContent = $request->request->get('rulePackageContent');

        if ($request->request->has('rulePackageHash') && trim($request->request->get('rulePackageHash'))) {
            if (hash('sha256', $rulePackageContent) !== $request->request->get('rulePackageHash')) {
                // Prepare the API debug data
                $debugInformation = [];
                if ($activeProject->isApiDebugMode()) {
                    $debugInformation['debugInformation'] = [
                        'reason' => 'rule_package_content_hash_invalid',
                        'sentHash' => $request->request->get('rulePackageHash'),
                        'generatedHash' => hash('sha256', $rulePackageContent),
                    ];
                }

                return new JsonResponse(['error' => true, 'errorMessage' => 'The specified hash is invalid for the given content.'] + $debugInformation);
            }

            $verifiedHash = true;
        }

        if (!trim($rulePackageContent)) {
            return new JsonResponse(['error' => true, 'errorMessage' => 'Rule package content is empty.']);
        }

        // Validate and process the content
        try {
            $this->rulePackageHelper->validateAndProcessContent($rulePackage, $rulePackageContent, false);
        } catch (\Exception $e) {
            // Prepare the API debug data
            $debugInformation = [];
            if ($activeProject->isApiDebugMode()) {
                $debugInformation['debugInformation'] = [
                    'reason' => 'general_error',
                    'exceptionMessage' => $e->getMessage(),
                ];
            }

            return new JsonResponse(['error' => true, 'errorMessage' => 'A general error occurred.'] + $debugInformation);
        }

        // Store the rule package cache
        $entityManager->flush();

        return new JsonResponse([
            'successful' => true,
            'verifiedHash' => $verifiedHash,
        ]);
    }

    protected function findRulePackageRuleCache(RulePackageCache $rulePackageCache, string $uuid): ?RulePackageRuleCache
    {
        $repository = $this->entityManager->getRepository(RulePackageRuleCache::class);

        return $repository->findOneBy([
            'rulePackageCache' => $rulePackageCache,
            'uuid' => $uuid,
        ]);
    }
}