<?php

namespace Mosparo\Controller\ProjectRelated;

use Mosparo\Exception\ExportException;
use Mosparo\Exception\ImportException;
use Mosparo\Helper\ExportHelper;
use Mosparo\Helper\ImportHelper;
use Mosparo\Helper\RuleTesterHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/project/{_projectId}/tools")
 */
class ToolsController extends AbstractController implements ProjectRelatedInterface
{
    use ProjectRelatedTrait;

    protected TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @Route("/rule-tester", name="tools_rule_tester")
     */
    public function ruleTester(Request $request, RuleTesterHelper $ruleTesterHelper): Response
    {
        $typeChoices = [
            'tools.ruleTester.types.textField' => 'textField',
            'tools.ruleTester.types.textarea' => 'textarea',
            'tools.ruleTester.types.emailField' => 'emailField',
            'tools.ruleTester.types.urlField' => 'urlField',
            'tools.ruleTester.types.userAgent' => 'userAgent',
            'tools.ruleTester.types.ipAddress' => 'ipAddress',
        ];
        $data = [
            'type' => 'textField',
            'useRules' => true,
            'useRulesets' => true,
        ];
        $form = $this->createFormBuilder($data, ['translation_domain' => 'mosparo'])
            ->add('value', TextareaType::class, [
                'label' => 'tools.ruleTester.form.value',
                'help' => 'tools.ruleTester.form.valueHelp',
                'attr' => [
                    'data-bs-toggle' => 'autosize'
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'tools.ruleTester.form.type',
                'help' => 'tools.ruleTester.form.typeHelp',
                'choices' => $typeChoices,
                'attr' => ['class' => 'form-select'],
            ])
            ->add('useRules', CheckboxType::class, [
                'label' => 'tools.ruleTester.form.useRules',
                'help' => 'tools.ruleTester.form.useRulesHelp',
                'required' => false,
            ])
            ->add('useRulesets', CheckboxType::class, [
                'label' => 'tools.ruleTester.form.useRulesets',
                'help' => 'tools.ruleTester.form.useRulesetsHelp',
                'required' => false,
            ])
            ->getForm();

        $form->handleRequest($request);

        $testData = [
            'value' => '',
            'type' => '',
            'useRules' => '',
            'useRulesets' => ''
        ];
        $submission = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $value = trim($form->get('value')->getData());
            $type = $form->get('type')->getData();
            $useRules = $form->get('useRules')->getData();
            $useRulesets = $form->get('useRulesets')->getData();

            $submission = $ruleTesterHelper->simulateRequest($value, $type, $useRules, $useRulesets);

            $testData = [
                'value' => $value,
                'type' => $type,
                'useRules' => $useRules,
                'useRulesets' => $useRulesets,
            ];
        }

        return $this->render('project_related/tools/rule_tester.html.twig', [
            'form' => $form->createView(),
            'submission' => $submission,
            'testData' => $testData,
        ]);
    }

    /**
     * @Route("/export", name="tools_export")
     */
    public function export(Request $request, ExportHelper $exportHelper): Response
    {
        $form = $this->createFormBuilder([], ['translation_domain' => 'mosparo'])
            ->add('generalSettings', CheckboxType::class, [
                'label' => 'tools.eiParts.generalSettings',
                'required' => false,
                'help' => 'tools.eiParts.generalSettingsHelp',
                'data' => true,
            ])
            ->add('designSettings', CheckboxType::class, [
                'label' => 'tools.eiParts.designSettings',
                'required' => false,
                'help' => 'tools.eiParts.designSettingsHelp',
                'data' => true,
            ])
            ->add('securitySettings', CheckboxType::class, [
                'label' => 'tools.eiParts.securitySettings',
                'required' => false,
                'help' => 'tools.eiParts.securitySettingsHelp',
                'data' => true,
            ])
            ->add('rules', CheckboxType::class, [
                'label' => 'tools.eiParts.rules',
                'required' => false,
                'help' => 'tools.eiParts.rulesHelp',
                'data' => true,
            ])
            ->add('rulesets', CheckboxType::class, [
                'label' => 'tools.eiParts.rulesets',
                'required' => false,
                'help' => 'tools.eiParts.rulesetsHelp',
                'data' => true,
            ])
            ->getForm();

        $form->handleRequest($request);

        $error = false;
        $errorMessage = null;
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $exportData = $exportHelper->exportProject(
                    $this->getActiveProject(),
                    $form->get('generalSettings')->getData(),
                    $form->get('designSettings')->getData(),
                    $form->get('securitySettings')->getData(),
                    $form->get('rules')->getData(),
                    $form->get('rulesets')->getData(),
                );

                $exportFileName = $exportHelper->generateFileName($this->getActiveProject());
                $jsonContent = json_encode($exportData);
                $response = new Response();

                $response->headers->set('Content-Type', 'application/json');
                $response->headers->set('Content-Disposition', 'attachment; filename="' . $exportFileName . '";');
                $response->headers->set('Content-Length', strlen($jsonContent));

                $response->sendHeaders();
                $response->setContent($jsonContent);

                return $response;
            } catch (ExportException $e) {
                $error = true;
                $errorMessage = $e->getMessage();
                if ($e->getCode() === ExportException::EMPTY_REQUEST) {
                    $errorMessage = 'tools.export.errorMessage.selectAtLeastOneElement';
                }
            }
        }

        return $this->render('project_related/tools/export.html.twig', [
            'form' => $form->createView(),
            'error' => $error,
            'errorMessage' => $errorMessage,
        ]);
    }

    /**
     * @Route("/import", name="tools_import")
     */
    public function import(Request $request, ImportHelper $importHelper): Response
    {
        $form = $this->createFormBuilder(['handlingExistingRules' => 'override'], ['translation_domain' => 'mosparo'])
            ->add('importFile', FileType::class, [
                'label' => 'tools.import.form.importFile',
                'required' => true,
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            'application/json',
                        ],
                        'mimeTypesMessage' => 'tools.import.importFile.correctFile',
                    ])
                ],
            ])
            ->add('generalSettings', CheckboxType::class, [
                'label' => 'tools.eiParts.generalSettings',
                'required' => false,
                'help' => 'tools.eiParts.generalSettingsHelp',
            ])
            ->add('designSettings', CheckboxType::class, [
                'label' => 'tools.eiParts.designSettings',
                'required' => false,
                'help' => 'tools.eiParts.designSettingsHelp',
            ])
            ->add('securitySettings', CheckboxType::class, [
                'label' => 'tools.eiParts.securitySettings',
                'required' => false,
                'help' => 'tools.eiParts.securitySettingsHelp',
            ])
            ->add('rules', CheckboxType::class, [
                'label' => 'tools.eiParts.rules',
                'required' => false,
                'help' => 'tools.eiParts.rulesHelp',
            ])
            ->add('rulesets', CheckboxType::class, [
                'label' => 'tools.eiParts.rulesets',
                'required' => false,
                'help' => 'tools.eiParts.rulesetsHelp',
            ])
            ->add('handlingExistingRules', ChoiceType::class, [
                'label' => 'tools.import.form.handlingExistingRules',
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'choices' => [
                    'tools.import.form.choice.override' => 'override',
                    'tools.import.form.choice.append' => 'append',
                    'tools.import.form.choice.add' => 'add'
                ]
            ])
            ->getForm();

        $form->handleRequest($request);

        $error = false;
        $errorMessage = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $importFile = $form->get('importFile')->getData();

            $originalFilename = pathinfo($importFile->getClientOriginalName(), PATHINFO_FILENAME);
            [$path, $fileName] = $importHelper->getImportFilePathAndName($this->getActiveProject());

            // We store the project id in the data to make sure that the import is using the correct project.
            // In theory, the user can switch to a different project and after that confirming the simulation.
            // If that is the case, then the import would use the wrong project.
            $importData = [
                'projectId' => $this->getActiveProject()->getId(),
                'originalFilename' => $originalFilename,
                'importDataFilename' => $fileName,
                'importGeneralSettings' => $form->get('generalSettings')->getData(),
                'importDesignSettings' => $form->get('designSettings')->getData(),
                'importSecuritySettings' => $form->get('securitySettings')->getData(),
                'importRules' => $form->get('rules')->getData(),
                'importRulesets' => $form->get('rulesets')->getData(),
                'handlingExistingRules' => $form->get('handlingExistingRules')->getData(),
            ];

            if (!$importData['importGeneralSettings'] && !$importData['importDesignSettings'] && !$importData['importSecuritySettings'] && !$importData['importRules'] && !$importData['importRulesets']) {
                $error = true;
                $errorMessage = 'tools.import.errorMessage.selectAtLeastOneElement';
            } else {
                try {
                    $importFile->move(
                        $path,
                        $fileName
                    );

                    $token = $importHelper->storeJobData($importData);
                } catch (FileException $e) {
                    $error = true;
                    $errorMessage = 'tools.import.errorMessage.fileUploadError';
                }
            }

            if (!$error) {
                return $this->redirectToRoute('tools_import_simulate', ['_projectId' => $this->getActiveProject()->getId(), 'token' => $token]);
            }
        }

        return $this->render('project_related/tools/import.html.twig', [
            'form' => $form->createView(),
            'error' => $error,
            'errorMessage' => $errorMessage,
        ]);
    }

    /**
     * @Route("/import/simulate/{token}", name="tools_import_simulate")
     */
    public function importSimulate(Request $request, ImportHelper $importHelper, $token = ''): Response
    {
        $form = $this->createFormBuilder(['token' => $token], ['translation_domain' => 'mosparo'])
            ->add('token', HiddenType::class)
            ->getForm();

        $form->handleRequest($request);

        $jobData = null;
        $importData = null;
        $changes = null;
        $hasChanges = false;
        $error = false;
        $errorMessage = null;
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $importHelper->executeImport($token);
            } catch (ImportException $e) {
                $error = true;
                $errorMessage = $e->getMessage();

                switch ($e->getCode()) {
                    case ImportException::PROJECT_NOT_AVAILABLE:
                        $errorMessage = 'tools.import.simulate.errorMessage.projectNotAvailable';
                        break;
                    case ImportException::NO_CHANGES_AVAILABLE:
                        $errorMessage = 'tools.import.simulate.errorMessage.noChangesAvailable';
                        break;
                    case ImportException::STORED_RULE_NOT_FOUND:
                        $errorMessage = 'tools.import.simulate.errorMessage.storedRuleNotFound';
                        break;
                    case ImportException::STORED_RULE_ITEM_NOT_FOUND:
                        $errorMessage = 'tools.import.simulate.errorMessage.storedRuleItemNotFound';
                        break;
                    case ImportException::STORED_RULESET_NOT_FOUND:
                        $errorMessage = 'tools.import.simulate.errorMessage.storedRulesetNotFound';
                        break;
                }
            }

            if (!$error) {
                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'success',
                    $this->translator->trans(
                        'tools.import.message.changesSuccessfullyExecuted',
                        [],
                        'mosparo'
                    )
                );

                return $this->redirectToRoute('tools_import', ['_projectId' => $this->getActiveProject()->getId()]);
            }
        }

        try {
            [$jobData, $importData, $hasChanges, $changes] = $importHelper->simulateImport($token);

            $jobData['changes'] = $changes;

            $importHelper->storeJobData($jobData, $token);
        } catch (ImportException $e) {
            $error = true;
            $errorMessage = $e->getMessage();

            switch ($e->getCode()) {
                case ImportException::JOB_DATA_FILE_NOT_FOUND:
                    $errorMessage = 'tools.import.simulate.errorMessage.jobDataFileNotFound';
                    break;
                case ImportException::JOB_DATA_INVALID:
                    $errorMessage = 'tools.import.simulate.errorMessage.jobDataInvalid';
                    break;
                case ImportException::IMPORT_FILE_NOT_FOUND:
                    $errorMessage = 'tools.import.simulate.errorMessage.importFileNotFound';
                    break;
                case ImportException::IMPORT_FILE_INVALID:
                    $errorMessage = 'tools.import.simulate.errorMessage.importFileInvalid';
                    break;
                case ImportException::PROJECT_NOT_AVAILABLE:
                    $errorMessage = 'tools.import.simulate.errorMessage.projectNotAvailable';
                    break;
                case ImportException::WRONG_SPECIFICATIONS_VERSION:
                    $errorMessage = 'tools.import.simulate.errorMessage.wrongSpecificationsVersion';
                    break;
            }
        }

        return $this->render('project_related/tools/import_simulate.html.twig', [
            'form' => $form->createView(),
            'hasChanges' => $hasChanges,
            'changes' => $changes,
            'jobData' => $jobData,
            'importData' => $importData,
            'error' => $error,
            'errorMessage' => $errorMessage,
        ]);
    }
}