<?php

namespace Mosparo\Controller\ProjectRelated;

use Doctrine\ORM\QueryBuilder;
use Mosparo\ApiClient\RequestHelper;
use Mosparo\Entity\Submission;
use Mosparo\Helper\CleanupHelper;
use Mosparo\Util\StringUtil;
use Mosparo\Verification\GeneralVerification;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/project/{_projectId}/submissions')]
class SubmissionController extends AbstractController implements ProjectRelatedInterface
{
    use ProjectRelatedTrait;

    #[Route('/', name: 'submission_list')]
    #[Route('/filter/{filter}', name: 'submission_list_filtered')]
    public function index(Request $request, DataTableFactory $dataTableFactory, CleanupHelper $cleanupHelper, $filter = ''): Response
    {
        if (!in_array($filter, ['spam', 'valid'])) {
            $filter = '';
        }

        $table = $dataTableFactory->create(['autoWidth' => true])
            ->add('id', TextColumn::class, ['label' => 'submission.list.id'])
            ->add('page', TwigColumn::class, [
                'label' => 'submission.list.page',
                'propertyPath' => 'submitToken.pageTitle',
                'template' => 'project_related/submission/list/_page.html.twig'
            ])
            ->add('data', TwigColumn::class, [
                'label' => 'submission.list.ipAddress',
                'template' => 'project_related/submission/list/_ipAddress.html.twig'
            ])
            ->add('spam', TwigColumn::class, [
                'label' => 'submission.list.spam',
                'template' => 'project_related/submission/list/_spam.html.twig',
                'className' => 'text-center border-left spam-column'
            ])
            ->add('spamRating', TwigColumn::class, [
                'label' => 'submission.list.spamRating',
                'template' => 'project_related/submission/list/_spamRating.html.twig',
                'className' => 'text-center spam-column'
            ])
            ->add('spamDetectionRating', TextColumn::class, ['visible' => false])
            ->add('submittedAt', TwigColumn::class, [
                'label' => 'submission.list.submittedAt',
                'template' => 'project_related/submission/list/_date.html.twig',
                'className' => 'text-center spam-column'
            ])
            ->add('valid', TwigColumn::class, [
                'label' => 'submission.list.valid',
                'template' => 'project_related/submission/list/_valid.html.twig',
                'className' => 'text-center border-left verification-column'
            ])
            ->add('verifiedAt', TwigColumn::class, [
                'label' => 'submission.list.verifiedAt',
                'template' => 'project_related/submission/list/_date.html.twig',
                'className' => 'text-center border-right verification-column'
            ])
            ->add('actions', TwigColumn::class, [
                'label' => 'submission.list.actions',
                'className' => 'buttons',
                'template' => 'project_related/submission/list/_actions.html.twig',
            ])
            ->addOrderBy('submittedAt', DataTable::SORT_DESCENDING)
            ->createAdapter(ORMAdapter::class, [
                'entity' => Submission::class,
                'query' => function (QueryBuilder $builder) use ($filter) {
                    $builder
                        ->select('e')
                        ->from(Submission::class, 'e');

                    if ($filter === 'spam') {
                        $builder
                            ->where('e.spam = TRUE')
                            ->orWhere('e.valid = FALSE');
                    } else if ($filter === 'valid') {
                        $builder
                            ->where('e.spam = FALSE')
                            ->andWhere('e.valid = TRUE');
                    } else {
                        $builder
                            ->where('e.spam = TRUE')
                            ->orWhere('e.valid IS NOT NULL');
                    }
                },
            ])
            ->handleRequest($request);

        $config = $this->getParameter('datatables.config');
        $table->setTemplate('project_related/submission/list/_table.html.twig', $config['template_parameters']);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('project_related/submission/list.html.twig', [
            'datatable' => $table,
            'filter' => $filter,
            'lastDatabaseCleanup' => $cleanupHelper->getLastDatabaseCleanup(),
        ]);
    }

    #[Route('/{id}/view', name: 'submission_view')]
    public function view(Submission $submission): Response
    {
        $activeProject = $this->projectHelper->getActiveProject();
        $minimumTimeActive = $submission->getProject()->getConfigValue('minimumTimeActive');
        $args = ['minimumTimeActive' => $minimumTimeActive];
        if ($minimumTimeActive) {
            $minimumTimeGv = $submission->getGeneralVerification(GeneralVerification::MINIMUM_TIME);

            $args['minimumTimeGv'] = $minimumTimeGv;
        }

        $verificationSimulationData = [];
        if ($activeProject->isVerificationSimulationMode()) {
            $formData = $this->prepareFormData($submission->getData());
            $formData['_mosparo_submitToken'] = $submission->getSubmitToken()->getToken();
            $formData['_mosparo_validationToken'] = $submission->getValidationToken();

            $requestHelper = new RequestHelper($activeProject->getPublicKey(), $activeProject->getPrivateKey());

            $cleanedFromData = $requestHelper->cleanupFormData($formData);
            $hashedFormData = $requestHelper->prepareFormData($cleanedFromData);
            $formDataSignature = $requestHelper->createFormDataHmacHash($hashedFormData);
            $validationSignature = '';
            if ($submission->getValidationToken()) {
                $validationSignature = $requestHelper->createHmacHash($submission->getValidationToken());
            }
            $apiEndpoint = '/api/v1/verification/verify';
            $requestData = [
                'submitToken' => $submission->getSubmitToken()->getToken(),
                'validationSignature' => $validationSignature,
                'formSignature' => $formDataSignature,
                'formData' => $hashedFormData,
            ];
            $requestDataJson = $requestHelper->toJson($requestData);
            $requestSignature = $requestHelper->createHmacHash($apiEndpoint . $requestDataJson);
            [$verifiedFields, $issues] = $this->generateVerifiedFields($submission);
            $verificationSimulationData['verificationSimulation'] = [
                'formData' => $formData,
                'cleanedFormData' => $cleanedFromData,
                'hashedFormData' => $hashedFormData,
                'formDataJson' => $requestHelper->toJson($hashedFormData),
                'publicKey' => $activeProject->getPublicKey(),
                'privateKey' => StringUtil::obfuscateString($activeProject->getPrivateKey()),
                'formDataSignature' => $formDataSignature,
                'validationSignature' => $validationSignature,
                'verificationSignature' => $requestHelper->createHmacHash($validationSignature . $formDataSignature),
                'apiEndpoint' => $apiEndpoint,
                'requestData' => $requestData,
                'requestDataJson' => $requestDataJson,
                'requestSignature' => $requestSignature,
                'response' => [
                    'valid' => var_export($submission->isValid(), true),
                    'verificationSignature' => $requestHelper->createHmacHash($validationSignature . $formDataSignature),
                    'verifiedFields' => $verifiedFields,
                    'issues' => $issues,
                ],
            ];
        }

        return $this->render('project_related/submission/view.html.twig', [
            'submission' => $submission,
            'generalVerifications' => $submission->getGeneralVerifications(),
        ] + $args + $verificationSimulationData);
    }

    protected function prepareFormData($submissionFormData): array
    {
        $formData = [];
        foreach ($submissionFormData['formData'] as $data) {
            $formData[$data['name']] = $data['value'];
        }

        return $formData;
    }

    protected function generateVerifiedFields(Submission $submission): array
    {
        $issues = [];
        foreach ($submission->getData()['formData'] as $data) {
            if (isset($data['type']) && $data['type'] === 'honeypot') {
                continue;
            }

            $key = $data['name'];
            $verificationResult = $submission->getVerifiedField($key);

            if ($verificationResult !== Submission::SUBMISSION_FIELD_VALID) {
                $issues[] = ['name' => $key, 'message' => 'Field not valid.'];
            }
        }

        return [$submission->getVerifiedFields(), $issues];
    }
}
